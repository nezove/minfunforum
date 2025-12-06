<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Список всех диалогов (для мобильных или когда не выбран диалог)
     */
    public function index()
    {
        $user = auth()->user();
        $conversations = $this->getUserConversations($user);

        return view('messages.index', compact('conversations'));
    }

    /**
     * Показать конкретный диалог
     */
    public function show(Request $request, $userId)
    {
        $currentUser = auth()->user();
        $otherUser = User::findOrFail($userId);

        if ($currentUser->id == $otherUser->id) {
            return redirect()->route('messages.index')
                ->with('error', 'Вы не можете отправлять сообщения самому себе');
        }

        $conversation = Conversation::findOrCreate($currentUser->id, $otherUser->id);

        // Получаем параметры пагинации
        $perPage = 30;
        $page = $request->get('page', 1);
        $search = $request->get('search', '');

        // Получаем сообщения с поиском
        $messagesQuery = $conversation->messages()->with('sender');
        
        if ($search) {
            $messagesQuery->where('content', 'LIKE', '%' . $search . '%');
        }

        // Сортировка: старые сверху, новые внизу
        $messages = $messagesQuery->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Группировка по датам
        $groupedMessages = $this->groupMessagesByDate($messages->items());

        // Отметить входящие сообщения как прочитанные
        $conversation->messages()
            ->where('sender_id', $otherUser->id)
            ->where('is_read', false)
            ->each(function ($message) {
                $message->markAsRead();
            });

        // Список всех диалогов для сайдбара
        $conversations = $this->getUserConversations($currentUser);

        // AJAX запрос для подгрузки истории
        if ($request->ajax()) {
            return response()->json([
                'messages' => $groupedMessages,
                'hasMore' => $messages->hasMorePages(),
                'currentPage' => $messages->currentPage(),
            ]);
        }

        return view('messages.show', compact(
            'conversation',
            'groupedMessages',
            'otherUser',
            'conversations',
            'search'
        ));
    }

    /**
     * Отправить сообщение
     */
    public function store(Request $request, $userId)
    {
        try {
            $request->validate([
                'content' => 'nullable|string|max:5000',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            ]);

            $currentUser = auth()->user();
            $otherUser = User::findOrFail($userId);

            if (empty($request->content) && !$request->hasFile('image')) {
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Сообщение не может быть пустым'
                    ], 422);
                }
                return back()->with('error', 'Сообщение не может быть пустым');
            }

        $conversation = Conversation::findOrCreate($currentUser->id, $otherUser->id);

        $imagePath = null;

        // Обработка изображения - конвертация в JPEG
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'msg_' . uniqid() . '.jpg';

            // Создаем менеджер изображений (Intervention Image v3)
            $manager = new ImageManager(new Driver());
            $img = $manager->read($image->getRealPath());

            // Изменяем размер если нужно
            if ($img->width() > 1920 || $img->height() > 1920) {
                $img->scale(width: 1920, height: 1920);
            }

            // Сохраняем в JPEG с качеством 85
            $path = 'messages/' . $filename;
            $encoded = $img->toJpeg(quality: 85);
            Storage::disk('public')->put($path, $encoded);

            $imagePath = $path;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $currentUser->id,
            'content' => $request->content,
            'image_path' => $imagePath,
        ]);

            $conversation->update(['last_message_at' => now()]);

            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message->load('sender'),
                ]);
            }

            return back()->with('success', 'Сообщение отправлено');
        } catch (\Exception $e) {
            \Log::error('Message send error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Произошла ошибка при отправке сообщения: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Произошла ошибка при отправке сообщения');
        }
    }

    /**
     * Удалить сообщение
     */
    public function destroy($messageId)
    {
        $message = Message::findOrFail($messageId);

        if ($message->sender_id != auth()->id()) {
            return back()->with('error', 'Вы не можете удалить это сообщение');
        }

        if ($message->image_path) {
            Storage::disk('public')->delete($message->image_path);
        }

        $message->delete();

        return back()->with('success', 'Сообщение удалено');
    }

    /**
     * Загрузить больше сообщений (AJAX)
     */
    public function loadMore(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = auth()->user();

        // Проверка доступа
        if ($conversation->user_one_id != $currentUser->id && $conversation->user_two_id != $currentUser->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $page = $request->get('page', 1);
        $beforeMessageId = $request->get('before', null);

        $query = $conversation->messages()->with('sender');

        if ($beforeMessageId) {
            $query->where('id', '<', $beforeMessageId);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        $groupedMessages = $this->groupMessagesByDate($messages);

        return response()->json([
            'success' => true,
            'messages' => $groupedMessages,
            'hasMore' => $messages->count() >= 30,
        ]);
    }

    /**
     * Поиск по сообщениям в диалоге
     */
    public function search(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $currentUser = auth()->user();

        if ($conversation->user_one_id != $currentUser->id && $conversation->user_two_id != $currentUser->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $search = $request->get('q', '');

        $messages = $conversation->messages()
            ->with('sender')
            ->where('content', 'LIKE', '%' . $search . '%')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'results' => $messages,
            'count' => $messages->count(),
        ]);
    }

    /**
     * Получить диалоги пользователя
     */
    private function getUserConversations($user)
    {
        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->get();

        foreach ($conversations as $conversation) {
            $conversation->otherUser = $conversation->getOtherUser($user->id);
            $conversation->unreadCount = $conversation->unreadCount($user->id);
            $conversation->lastMsg = $conversation->lastMessage;
        }

        return $conversations;
    }

    /**
     * Группировка сообщений по датам
     */
    private function groupMessagesByDate($messages)
    {
        $grouped = [];
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        foreach ($messages as $message) {
            $messageDate = $message->created_at->startOfDay();

            if ($messageDate->equalTo($today)) {
                $dateLabel = 'Сегодня';
            } elseif ($messageDate->equalTo($yesterday)) {
                $dateLabel = 'Вчера';
            } else {
                $dateLabel = $message->created_at->isoFormat('D MMMM');
            }

            if (!isset($grouped[$dateLabel])) {
                $grouped[$dateLabel] = [];
            }

            $grouped[$dateLabel][] = $message;
        }

        return $grouped;
    }
}
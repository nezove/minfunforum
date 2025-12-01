# Инструкция по публикации проекта на GitHub

## ВАЖНО: Проверка безопасности перед публикацией

### 1. Проверьте, что файл .env НЕ будет загружен

Убедитесь, что файл `.env` находится в `.gitignore` (уже добавлен):

```bash
cat .gitignore | grep .env
```

**КРИТИЧНО**: Файл `.env` содержит секретные данные:
- APP_KEY (ключ шифрования Laravel)
- HCAPTCHA_SECRET_KEY (секретный ключ hCaptcha)
- STOPFORUMSPAM_API_KEY (API ключ StopForumSpam)
- DB_PASSWORD (пароль базы данных)

**НИКОГДА не публикуйте .env файл в открытом доступе!**

### 2. Проверьте скомпилированные файлы

Убедитесь, что в `.gitignore` исключены:
- `/node_modules` - зависимости Node.js
- `/vendor` - зависимости Composer
- `/public/build` - скомпилированные frontend-файлы
- `/storage` - логи и кеш
- `.DS_Store` - системные файлы macOS

## Шаги публикации на GitHub

### Шаг 1: Инициализация Git-репозитория

```bash
# Перейдите в директорию проекта
cd /Users/nick/Sites/localhost

# Инициализируйте git-репозиторий
git init

# Проверьте, что .env не будет добавлен
git status | grep .env

# Если .env отображается в списке файлов, ОСТАНОВИТЕСЬ и проверьте .gitignore!
```

### Шаг 2: Создайте первый коммит

```bash
# Добавьте все файлы (кроме тех, что в .gitignore)
git add .

# Создайте первый коммит
git commit -m "Initial commit: Laravel forum application"
```

### Шаг 3: Создайте репозиторий на GitHub

1. Перейдите на https://github.com
2. Нажмите кнопку "+" в правом верхнем углу
3. Выберите "New repository"
4. Заполните:
   - Repository name: например, `laravel-forum`
   - Description: "Laravel-based forum application with advanced security features"
   - Выберите Public или Private
   - НЕ добавляйте README, .gitignore или LICENSE (у вас уже есть эти файлы)
5. Нажмите "Create repository"

### Шаг 4: Свяжите локальный репозиторий с GitHub

```bash
# Добавьте удаленный репозиторий (замените YOUR_USERNAME и YOUR_REPO)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git

# Проверьте, что remote добавлен
git remote -v

# Отправьте код на GitHub
git branch -M main
git push -u origin main
```

### Шаг 5: Проверка после публикации

После публикации проверьте на GitHub:

1. **Файл .env НЕ должен быть виден** в репозитории
2. Файл `.env.example` должен быть виден с примерами настроек
3. README.md должен отображаться с инструкциями

### Шаг 6: Настройка для других разработчиков

Добавьте в описание репозитория на GitHub инструкцию по установке:

```
1. Clone the repository
2. Run: composer install
3. Run: npm install
4. Copy .env.example to .env
5. Configure your .env file with your credentials
6. Run: php artisan key:generate
7. Run: php artisan migrate
8. Run: npm run build
```

## Что делать, если вы случайно загрузили .env

### Если .env еще НЕ отправлен на GitHub:

```bash
# Удалите файл из индекса git
git rm --cached .env

# Убедитесь, что .env в .gitignore
echo ".env" >> .gitignore

# Создайте новый коммит
git add .gitignore
git commit -m "Remove .env from tracking"
```

### Если .env УЖЕ отправлен на GitHub:

**КРИТИЧНО**: Секретные данные скомпрометированы!

1. **Немедленно смените все секретные ключи:**
   ```bash
   # Сгенерируйте новый APP_KEY
   php artisan key:generate

   # Получите новые ключи на https://www.hcaptcha.com/
   # Получите новый API key на https://www.stopforumspam.com/keys
   # Смените пароль базы данных
   ```

2. **Удалите файл из истории Git:**
   ```bash
   # Используйте BFG Repo-Cleaner или git filter-branch
   # Это сложная операция, изучите документацию!
   ```

3. **Форсированно обновите репозиторий:**
   ```bash
   git push --force origin main
   ```

## Дополнительные рекомендации безопасности

### 1. GitHub Secrets для CI/CD

Если используете GitHub Actions, храните секреты в Settings → Secrets:
- `APP_KEY`
- `HCAPTCHA_SECRET_KEY`
- `STOPFORUMSPAM_API_KEY`
- `DB_PASSWORD`

### 2. Защита веток

В настройках репозитория (Settings → Branches):
- Включите branch protection для `main`
- Требуйте code review перед merge
- Запретите force push

### 3. Dependabot

Включите Dependabot для автоматических обновлений зависимостей:
- Settings → Security → Dependabot alerts

### 4. .gitignore проверка

Перед каждым коммитом проверяйте:

```bash
# Посмотрите, что будет закоммичено
git status

# Убедитесь, что нет секретных файлов
git diff --cached
```

## Быстрая проверка безопасности

Перед `git push` выполните:

```bash
# 1. Проверьте, что .env не в индексе
git ls-files | grep .env

# Если команда ничего не вывела - хорошо!
# Если вывела .env - СТОП! Удалите его из индекса

# 2. Проверьте последний коммит
git log -1 --stat

# 3. Просмотрите изменения
git show HEAD
```

## Полезные команды

```bash
# Посмотреть все игнорируемые файлы
git status --ignored

# Проверить, будет ли файл проигнорирован
git check-ignore -v .env

# Посмотреть все отслеживаемые файлы
git ls-files

# Удалить файл из отслеживания (но не удалять физически)
git rm --cached filename
```

## Контрольный чеклист перед публикацией

- [ ] .env файл в .gitignore
- [ ] .env.example обновлен без секретных данных
- [ ] README.md содержит инструкции по установке
- [ ] Все секретные ключи заменены на примеры в .env.example
- [ ] node_modules и vendor в .gitignore
- [ ] Проверено: `git status | grep .env` не показывает .env
- [ ] Проверено: нет секретных данных в коде
- [ ] Создан репозиторий на GitHub
- [ ] Выполнен `git push`
- [ ] Проверен репозиторий на GitHub - .env не виден

## Помощь

Если у вас возникли проблемы:
1. НЕ публикуйте репозиторий, пока не разберетесь
2. Проверьте .gitignore
3. Проверьте `git status`
4. При необходимости получите помощь в сообществе Laravel

---

**ПОМНИТЕ**: Однажды загруженные секретные данные на GitHub остаются в истории навсегда, даже если вы их удалите. Будьте внимательны!

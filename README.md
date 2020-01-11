# b01110011.recaptcha

reCAPTCHA v3 - это бесплатный сервис, который защищает ваш сайт от спама. Капча является невидимой для пользователей. *(что означает, не нужно больше тыкать на картинки или вводить текст)*  

Модуль встраивает данный механизм защиты на сайт.  
  
**Для работы модуля:**
1. Получить ключи рекапчи.
2. Вставить скрытое поле в форму.
3. Произвести быструю настройку на странице настроек модуля.

**Получение ключей:**

Авторизоваться через google аккаунт и зарегистрировать свой сайт по ссылке  
https://www.google.com/recaptcha/admin/create  
и получить ключи.  
*(при ситуации когда есть тестовый сайт, вписывайте его в поле "Домены")*  

**Вставить код в формы:**

скрытое поле в которое будет вставляться токен при отправке формы
```html
<input type="hidden" name="recaptcha_token" value="">
```

**Если нужно защитить от спама:**

Регистрацию  
> Переходим во вкладку и жмём на "Включить капчу"  
  
Добавление данных в модуль "Веб формы"  
> Переходим во вкладку и выбираем нужные формы для защиты.    
  
Добавление данных в модуль "Инфоблоки"  
> Переходим во вкладку и выбираем нужные инфоблоки для защиты.  

**Как получить токен, если я отправляю данные через AJAX?**
```js
window.recaptcha.getToken()
```

**Как работает модуль?** *(для программистов)*

js часть:  
При загрузке страницы, в объекте window создаётся объект recaptcha, в котором запрашивается и хранится токен.  
Каждые 100 секунд токен обновляется.  
Далее ищутся все поля с именем recaptcha_token *(input[name=recaptcha_token])* и к формам в которых эти поля находятся, вешается событие onsubmit.  
При отправке формы помещается токен в скрытое поле и запрашивается новый токен.  
Такой механизм запроса токена заранее, реализован с целью убрать задержку перед отправкой формы.  
  
php часть:  
При построении каждой страницы, регистрируются события для проверки токена, отправленного вместе с формой. Этот токен и секретный ключ отправляются на сервер гугла для получения результата.  
Если токен верный и пришедшая оценка выше или равна той что в настройках, тогда данные формы обрабатываются, иначе выводится текст с ошибкой.  

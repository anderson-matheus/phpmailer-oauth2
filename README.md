# PHPMailer6 and Oauth2

Send email using phpmailer6 and authentication oauth2 with gmail.

#### Requirements
  - Composer
  - PHP7 or higher

#### References

* [Gmail with XOAUTH2](https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2) - PHPMailer - Using Gmail with XOAUTH2
* [Documentation oauth2](https://developers.google.com/identity/protocols/oauth2) - Google documentation oauth2
* [Documentation api oauth2](https://developers.google.com/identity/protocols/oauth2/web-server) - Using OAuth 2.0 for Web Server Applications
* [Code base to implementation](https://github.com/PHPMailer/PHPMailer/issues/1616)

#### Install

```sh
$ git clone ...
$ cd ....
$ composer install
$ cp .env.example .env
```

#### Convigure env vars

```sh
CLIENT_ID="your_google_client_id"
CLIENT_SECRET="your_google_client_secret"
REDIRECT_URI="your_google_redirect_uri"
SCOPE="https://www.googleapis.com/auth/gmail.send"
RESPONSE_TYPE="code"
ACCESS_TYPE="offline"
PROMPT="consent"
EMAIL="your_gmail"
EMAIL_NAME="your_gmail_name"
```

# phpwoo
Speed up your php applications with swoole!

## Compile Swoole
```sh
# install hiredis
$ git clone https://github.com/redis/hiredis.git && cd hiredis
$ make
$ sudo make install
$ sudo ldconfig
# compile swoole
$ git clone https://github.com/swoole/swoole-src.git && cd swoole-src/
$ phpize
$ ./configure --enable-coroutine --enable-async-redis --enable-openssl
$ make
$ sudo make install
# HOMEWORK: enable swoole extension
# check installation
$ php -i | grep swoole
```

## Installation
```sh
$ composer require jndrm/phpwoo
```

### Laravel
```sh
# publish configuration
$ php artisan vendor:publish --provider="Drmer\Phpwoo\Laravel\PhpwooServiceProvider"
# start phpwoo server
$ php artisan phpwoo
```

### Work with Nginx
```
server {
    listen 80;

    server_name YOUR_DOMAIN;

    root YOUR_DOCUMENT_ROOT;

    location / {
        if (!-e $request_filename) {
            proxy_pass http://127.0.0.1:3737;
        }
    }
    location = /index.php {
        proxy_pass http://127.0.0.1:3737;
    }
    location = / {
        proxy_pass http://127.0.0.1:3737;
    }
}
```

## TODOs
- [x] Add Laravel Support
- [ ] Add Lumen Support
- [ ] Add Symfony Support
- [ ] Add Yii2 Support
- [ ] Add CodeIgniter Support
- [ ] Add Zend Framework Support
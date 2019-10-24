# Laboratório
### Projeto de TCC do curso Análise e Desenvolvimento de Sistemas
Gitlab classroom - api de acesso ao gitlab

### To set it up
Setup using laradock (https://laradock.io/)
* run `docker-compose up -d nginx mysql phpmyadmin workspace`
* run `cp .env.example .env`
* replace values in `DB_USER` and `DB_PASSWORD` for the credentials found on laradock/.env


### To run laravel commands
* run `docker-compose exec workspace bash` to enter the Workspace container to execute commands like (Artisan, Composer, PHPUnit, Gulp, …)

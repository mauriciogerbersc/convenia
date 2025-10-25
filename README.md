API de Colaboradores (Laravel 8)

API REST para gerenciar colaboradores por gestor (usuário autenticado), com JWT, importação CSV assíncrona em fila e cache na listagem.

✅ O que está coberto

Login + autenticação por token (JWT)

CRUD de colaboradores apenas do gestor logado

Validações obrigatórias: name, email, cpf, city, state

Importação em massa (CSV) com notificação por e-mail

Fila para processar o CSV (assíncrono)

Cache na listagem de colaboradores

Testes de controller e import

Observação: o campo state pode ter até 50 caracteres (ajustado por migration + validação).

🚀 Subindo o projeto (Docker)
# 1) Copie o .env
cp .env.example .env

# 2) Suba os containers
docker-compose up -d

# 3) Instale dependências
docker-compose run --rm app composer install

# 4) Configurar JWT
docker-compose run --rm app php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
docker-compose run --rm app php artisan jwt:secret

# 5) Migrar e semear usuários
docker-compose run --rm app php artisan migrate
docker-compose run --rm app php artisan db:seed --class=UserSeeder

# 6) (Fila usando database)
docker-compose run --rm app php artisan queue:table
docker-compose run --rm app php artisan queue:failed-table
docker-compose run --rm app php artisan migrate
docker-compose run --rm app php artisan queue:work --queue=imports,default


.env essencial

APP_ENV=local
APP_DEBUG=true
MAIL_MAILER=log         # e-mails no storage/logs/laravel.log
QUEUE_CONNECTION=database
CACHE_DRIVER=file       # ou redis


Seeds criam usuários:

gestojohndoe@convenia.com.br
 / 123456

🔐 Autenticação

Login
POST /api/login

curl -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"gestor@example.com","password":"secret"}'


Resposta:

{ "token": "<JWT>",  "expires_in": 3600 }


Use nos endpoints protegidos:

Authorization: Bearer <JWT>
Accept: application/json
Content-Type: application/json

🧭 Endpoints

Dica de rotas: registre /api/employees/import ANTES do apiResource('employees', ...) para evitar conflito com {employee} e erro 405.

Listar (com cache)

GET /api/employees

Criar

POST /api/employees

{
  "name": "Ana",
  "email": "ana@x.com",
  "cpf": "123.456.789-01",
  "city": "Florianópolis",
  "state": "Santa Catarina"
}


O CPF é normalizado para dígitos (remove . e -) antes de validar/salvar.

Atualizar

PUT /api/employees/{employee}
(403 se não for o dono)

Remover

DELETE /api/employees/{employee}
(403 se não for o dono)

Importar CSV (assíncrono)

POST /api/employees/import (multipart/form-data)

curl -X POST http://localhost:8000/api/employees/import \
  -H "Authorization: Bearer <JWT>" -H "Accept: application/json" \
  -F "file=@employees.csv"


Cabeçalho do CSV (obrigatório):

name,email,cpf,city,state


Resposta: 202 Accepted com o nome salvo do arquivo.

O processamento roda na fila imports e ao final o gestor recebe o e-mail com:
“Processamento realizado com sucesso”.

⚙️ Fila (importação)

Controller salva o arquivo em storage/app/imports/{user_id}/{uuid}.csv

Job ProcessEmployeesImport (fila imports):

valida header

normaliza CPF (apenas dígitos) e state (maiúsculas)

valida cada linha (obrigatórios + unicidade por gestor)

cria colaboradores do gestor logado

envia notificação por e-mail com a mensagem exigida

Worker:

docker-compose run --rm app php artisan queue:work --queue=imports,default
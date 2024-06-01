Installation:
1. Install composer and run `composer update` with all recipes
2. Open the .env file and set the database configuration
3. Install symfony CLI
4. Create Database `php bin/console doctrine:database:create`
5. Run migrations `php bin/console doctrine:migrations:migrate`
6. Configure JWT bundle `php bin/console lexik:jwt:generate-keypair` 
7. Run dev server `symfony server:start`

API Methods:
1. POST `/api/register` - create new User. Uncomment line 46 to create a user with Admin Role
2. POST `/api/login` - authentication with email and passwords. JWT token returned. Token TTL = 1 hour.
3. GET `/api/user/list` - get all users. Admin privilegies needed
4. POST `/api/user/create` - creates new user. Admin privilegies needed
5. GET `/api/user/{id}` - get single user. Admin can get any user, regular user can return only himself.
6. PUT `/api/user/{id}` - update user. Admin can update any user, regular user can update only himself.
7. DELETE `/api/user/{id}` - delete single user. Allowed for ROLE_ADMIN only

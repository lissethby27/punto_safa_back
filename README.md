##📚 PuntoSafa - Backend
El backend de PuntoSafa es la API que gestiona la lógica de negocio y la comunicación con la base de datos. 
Está desarrollado con PHP y Symfony, proporcionando endpoints seguros y eficientes para la gestión de libros, usuarios y compras.

🚀 Tecnologías utilizadas
PHP >= versión 8.3.6
Symfony >= versión 6
Base de datos:  PostgreSQL 
Herramienta de construcción: Composerç

##🔧 Instalación
##1️⃣ Clonar el repositorio
https://github.com/lissethby27/punto_safa_back.git

##2️⃣ Instalar dependencias

composer install

##3️⃣ Crear la base de datos y ejecutar migraciones

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

##4️⃣  Ejecutar el servidor
symfony serve start

##👷 Desarrolladores

@amsafa
@Pablo-R-B
@lissethby27

#🌍 URL de producción en Render
##🔗 Backend desplegado en Render:
https://punto-safa-back-2.onrender.com

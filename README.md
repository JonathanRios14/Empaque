<p align="center">
   <img src="public/images/plasencia-logocafe.png" width="220" alt="Plasencia Logo">
</p>

# Sistema de Empaque

Proyecto en desarrollo utilizando Laravel.

## Estado del proyecto

Este sistema se encuentra actualmente en proceso de desarrollo, por lo que algunas funcionalidades pueden cambiar, mejorarse o agregarse conforme avance el proyecto.

## Tecnologías utilizadas

* Laravel 10
* PHP
* MySQL
* Blade
* Tailwind CSS
* Alpine.js
* Vite

## Instalación básica

Clonar el repositorio:

```bash
git clone URL_DEL_REPOSITORIO
```

Entrar al proyecto:

```bash
cd nombre-del-proyecto
```

Instalar dependencias de PHP:

```bash
composer install
```

Instalar dependencias de Node:

```bash
npm install
```

Copiar el archivo de entorno:

```bash
cp .env.example .env
```

Generar la clave de la aplicación:

```bash
php artisan key:generate
```

Configurar la base de datos en el archivo `.env`.

Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

Compilar archivos del frontend:

```bash
npm run build
```

Levantar el proyecto:

```bash
php artisan serve
```

## Desarrollo

Para trabajar con los estilos y scripts en modo observación:

```bash
npm run build -- --watch
```


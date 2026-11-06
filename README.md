# Reality Social

Red social (estilo X) de la comunidad Reality APP. Proyecto **independiente** de la tienda.

- URL: https://realityapp.org/social
- Repo: `douglasvelezv/realityapp-social` → se despliega en `public_html/social`.
- Stack: PHP 8.1+ · MySQL · HTML/CSS/JS (sin frameworks).

## Login compartido (importante)
El inicio de sesión es **el mismo de toda realityapp.org**: usa la sesión PHP
compartida (cookie `ramsid`, `path=/`) y la tabla `users` de la base compartida
`u712716917_market`. Aquí **no se crean usuarios nuevos ni se guardan contraseñas**:
la identidad (nombre, usuario y medio Google/X/correo) se lee de esa base.
Cada proyecto guarda **sus propios datos** en su propia base.

## Instalación en Hostinger
1. **Base de datos propia:** en hPanel → Bases de datos, crea
   `u712716917_social` con el usuario `u712716917_socialapp` y una contraseña fuerte.
2. **Importar el esquema:** entra en phpMyAdmin de esa base e importa `database.sql`.
3. **Secretos (fuera de la web):** copia `config-example/social.sample.php` a
   `domains/realityapp.org/config/social.php` y rellena:
   - credenciales de `u712716917_social` (esta red),
   - credenciales de `u712716917_market` (solo para leer el login).
4. **Git Deploy:** en hPanel → Git, conecta este repo con ruta de instalación
   `public_html/social`, rama `main`, y activa el despliegue automático.
5. Comprueba `https://realityapp.org/social/api/health.php`: debe responder
   `db_social: true`, `db_login: true`.

## Estructura
```
config.php            Carga los secretos desde fuera de public_html
database.sql          Esquema de la base propia (posts, likes, follows…)
includes/             db (2 conexiones), session (compartida), functions
auth/                 Puente al login compartido (login, logout, register)
api/                  Endpoints fetch (likes, follow, post…)
assets/{css,js,images}
uploads/              Fotos (perfil, banner, posts) — sin ejecutar código
index.php explore.php profile.php post.php notifications.php settings.php search.php
```

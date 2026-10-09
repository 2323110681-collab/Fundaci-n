# Fundación Desarollo Universitaro

Este proyecto es una aplicación web en PHP para gestionar contenido institucional de Fundación DU. Incluye un frontend estático en HTML/CSS/JS y un backend PHP ligero con API REST para noticias, programas, agenda y administración de usuarios.

## Características

- Gestión de programas, noticias, eventos, miembros y convenios
- Página informativa del Congreso Internacional CDIA con ponentes, tarifas y medios de pago
- Panel administrativo con inicio de sesión y control de acceso
- Creación de usuarios con rol `admin` o `editor` (solo administradores)
- Creación, edición y eliminación de registros
- Panel dividido en secciones navegables desde el sidebar
- Búsqueda de registros y confirmaciones CRUD con SweetAlert2
- Modo claro/oscuro en el panel administrativo
- Registro de reclamaciones en MySQL con folio y constancia imprimible
- Protección CSRF para autenticación, mutaciones del API y envío de reclamaciones
- Subida de imágenes hacia `public/uploads`
- Aviso emergente de inicio configurable desde el panel, con imagen, enlace opcional, fechas de publicación y cierre automático
- Autenticación y sesiones mediante `public/auth.php`
- API JSON consumible desde frontend o herramientas como Postman

## Estructura del proyecto

- `app/Controllers`: controladores para noticias, programas y agenda
- `app/Models`: modelos de acceso a datos
- `app/Core`: clases base para autoloading y enrutamiento
- `config/`: configuración de la base de datos
- `database/`: esquema SQL y datos iniciales
- `public/`: archivos públicos, entrada principal del API y recursos estáticos
- `public/assets/css/`: estilos compartidos y específicos
- `public/assets/js/`: scripts de frontend y admin

## Requisitos

- XAMPP o un entorno similar con:
  - Apache
  - MySQL
  - PHP
- Navegador web moderno

## Instalación y configuración

1. Copia el proyecto dentro de la carpeta `htdocs` de XAMPP, por ejemplo:
   - `C:\xampp\htdocs\fundaciondu`

2. Inicia Apache y MySQL desde el Panel de Control de XAMPP.

3. Crea la base de datos y carga el esquema SQL:
   - Abre phpMyAdmin
   - Crea una base de datos llamada `fundaciondu`
   - Importa `database/fundaciondu.sql`

4. Verifica la configuración de conexión en `config/database.php`. Por defecto está preparada para:
   - host: `localhost`
   - usuario: `root`
   - contraseña: `""` (vacía)

Si la base de datos ya estaba creada y le faltan columnas nuevas, el archivo `database/fundaciondu.sql` ya las incluye todas: basta con volver a importarlo o ejecutar solo los `ALTER TABLE` que falten. No hay carpeta `database/migrations/`.

5. Accede al proyecto desde el navegador:
   - `http://localhost/fundaciondu/public/`
   - Congreso CDIA: `http://localhost/fundaciondu/public/congreso`
   - Administración del Congreso CDIA: `http://localhost/fundaciondu/public/admin-congreso` (requiere iniciar sesión)

## Correo del formulario de contacto

El formulario envía las notificaciones con `mail()` de PHP, sin depender de PHPMailer ni de la carpeta `vendor/`. El hosting debe tener habilitado y configurado el envío de correo para PHP. Que `mail()` acepte el mensaje no garantiza que llegue a la bandeja de entrada.

1. Copia `config/mail.local.php.example` como `config/mail.local.php` y configura el correo que recibirá las consultas y la dirección remitente de tu dominio.
2. En cPanel revisa **Email Deliverability** para confirmar SPF y DKIM; usa **Track Delivery** para comprobar si el servidor aceptó, rechazó o entregó el mensaje.
3. Si el formulario guarda la consulta pero muestra que no se pudo enviar el correo, revisa los registros de errores de PHP y consulta con el hosting si `mail()` está habilitado para tu cuenta.

La carpeta `config/` está protegida para impedir que sus archivos sean descargados desde el navegador. No publiques ni compartas `config/mail.local.php`.

## Panel administrativo

El panel administrativo está en `public/admin.php` y requiere iniciar sesión en `public/login.php`.

La opción **Aviso emergente** del sidebar (también disponible en `public/admin.php?section=popup` y desde la dirección anterior `public/admin-popup`) permite subir el afiche, definir desde qué fecha se muestra y hasta qué fecha está vigente, activar o pausar su publicación y elegir el tiempo en pantalla (0 segundos mantiene el aviso hasta que la persona lo cierre). El aviso aparece en la página de inicio una vez por sesión del navegador. La tabla de configuración se incluye en `database/fundaciondu.sql` y también se crea automáticamente si aún no existe.

Cada opción del sidebar abre una sección propia dentro del panel:

- Programas: `public/admin.php?section=programas`
- Noticias: `public/admin.php?section=noticias`
- Eventos: `public/admin.php?section=agenda`
- Miembros: `public/admin.php?section=miembros`
- Convenios: `public/admin.php?section=convenios`
- Congreso CDIA: `public/admin.php?section=congreso`
- Usuarios: `public/admin.php?section=usuarios` (solo administradores)

El panel permite:

- Administrar programas, noticias, eventos, miembros, convenios y la presentación del Congreso CDIA
- Crear registros con un formulario que cambia según la sección
- Editar registros mediante modal
- Consultar y buscar registros en la tabla situada debajo del formulario
- Confirmar eliminaciones y ver resultados de las operaciones CRUD con SweetAlert2
- Usar el sidebar fijo, el encabezado del panel y el modo claro/oscuro
- Crear usuarios nuevos si la persona autenticada es administradora
- Editar título, presentación y logo del Congreso desde el mismo formulario y listado que las demás secciones
- Elegir el logo desde el equipo en `public/admin.php?section=congreso`, con vista previa antes de guardar
- Crear un congreso desde el botón **Crear congreso** en la pantalla de presentación; abre el formulario completo para agregar título, presentación, tarifas, pagos, auspiciadores y ponentes
- Consultar y editar los congresos desde la tabla de `public/admin.php?section=congreso`; cada uno tiene tarifas, pagos, auspiciadores y ponentes independientes
- Administrar tarifas, pagos y ponentes desde `public/admin-congreso.php?id=ID`, con el mismo diseño del panel
- La tabla `congresos` se crea automáticamente y conserva el Congreso CDIA migrando el registro existente de `congreso_contenido`; no hace falta volver a importar el SQL
- Abrir el editor general desde `public/admin.php?section=congreso&editar=1`; permite actualizar todos los datos del Congreso en una sola pantalla
- Subir el logo desde el equipo y ver su vista previa antes de guardarlo
- Quitar el QR de inscripción desde la X de su vista previa y guardar los cambios para retirarlo del congreso publicado
- Agregar o editar ponentes, seleccionar su país y obtener automáticamente su bandera y agrupación pública
- Subir fotografías de ponentes desde el equipo e insertar imágenes en sus biografías con Quill

La página de inicio muestra el Congreso junto a los programas, con la etiqueta «Congreso» y su logo; cada tarjeta de programa se identifica como «Programa».

En el formulario, los eventos nuevos reciben el estado `proximo` automáticamente. Las noticias creadas sin categoría reciben `General`.

La tabla `convenios` no incluye registros de ejemplo y permanece vacía hasta que se creen convenios desde el panel.

El formulario público de `public/reclamaciones.php` registra las solicitudes en la tabla `reclamaciones` y presenta una constancia imprimible con folio. El proveedor debe gestionar y responder cada solicitud dentro del plazo legal.

## Autenticación y usuarios

- Iniciar sesión:
  - `POST /fundaciondu/public/auth.php`
  - Formulario `username` y `password`
- Ver usuario actual:
  - `GET /fundaciondu/public/auth.php`
- Cerrar sesión:
  - `GET /fundaciondu/public/auth.php?action=logout`
- Crear usuario (solo admin):
  - `POST /fundaciondu/public/auth.php?action=create-user`
  - Campos: `username`, `password`, `role`
- Listar usuarios (solo admin):
  - `GET /fundaciondu/public/auth.php?action=list-users`

## Uso del API

La entrada principal del backend está en `public/index.php`.

### Noticias

- Listar noticias:
  - `GET /fundaciondu/public/index.php?resource=noticias`
- Ver una noticia por ID:
  - `GET /fundaciondu/public/index.php?resource=noticias&id=1`
- Crear noticia:
  - `POST /fundaciondu/public/index.php?resource=noticias`
- Actualizar noticia:
  - `PUT /fundaciondu/public/index.php?resource=noticias&id=1`
- Eliminar noticia:
  - `DELETE /fundaciondu/public/index.php?resource=noticias&id=1`

### Programas

- Listar programas:
  - `GET /fundaciondu/public/index.php?resource=programas`
- Ver un programa por ID:
  - `GET /fundaciondu/public/index.php?resource=programas&id=1`
- Crear programa:
  - `POST /fundaciondu/public/index.php?resource=programas`
- Los campos ampliados de un programa son opcionales y se devuelven tanto al listar como al consultar por ID. `temario` acepta una lista JSON de `{ "titulo", "descripcion" }`; `docentes` acepta una lista JSON de `{ "foto", "nombre", "cargo", "descripcion", "correo", "linkedin" }`.
- Para crear o editar estos datos desde el panel, agrega módulos y docentes con los formularios repetibles. La foto docente puede ser una URL o un archivo JPG, PNG, GIF o WEBP de hasta 2 MB; el API conserva las listas en formato JSON.

### Agenda / Eventos

- Listar eventos:
  - `GET /fundaciondu/public/index.php?resource=agenda`
- Alias de evento:
  - `GET /fundaciondu/public/index.php?resource=eventos`

## Notas importantes

- Los archivos subidos se almacenan en `public/uploads`.
- El frontend es HTML/CSS/JS puro; no se usa framework como Laravel, Symfony, React, Vue o Angular.
- El backend es PHP puro con enrutamiento personalizado en `public/index.php`.
- El login y la gestión de usuarios usan `public/auth.php`.
- Las URLs públicas no llevan la extensión: `.htaccess` traduce `/noticias` a `noticias-page.php`. Los enlaces antiguos con `.php` siguen funcionando.
- Las rutas de las imágenes se calculan en `app/Helpers/UploadPath.php` a partir de la URL real, así que el proyecto funciona igual en `localhost/fundaciondu/public` o en la raíz de un dominio. Si mueves el sitio de sitio, ejecuta `database/migrate_upload_prefix.php` o `database/restore_upload_prefix.php` según corresponda.

## Recomendaciones

- Protege las carpetas y la base de datos en producción
- Mejora la validación de formularios en el backend y frontend
- Añade paginación y filtros avanzados para el panel administrativo
- Separa más claramente frontend y backend si el proyecto crece

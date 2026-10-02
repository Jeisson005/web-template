# Backend Services & Serverless/PHP Endpoints

Este directorio contiene los scripts y endpoints de backend auxiliares para entornos de hosting compartido (cPanel / Apache / GoDaddy):

- **`api/contact.php`**: Endpoint para procesamiento de formularios de contacto (validación de reCAPTCHA, reenvío a Webhook o CRM).
- **`ssl_auto/renew_ssl.php`**: Script de mantenimiento para emisión y renovación automatizada de certificados SSL vía ACME v2 / Let's Encrypt mediante tareas Cron de cPanel.
- **`.htaccess`**: Reglas de enrutamiento limpio y protección de archivos de claves y certificados.

## Despliegue

Este directorio se despliega de forma independiente al frontend. Mientras el `frontend` puede alojarse en cualquier CDN estático (Cloudflare Pages, Vercel, S3, Netlify), el `backend` se aloja en el servidor con soporte PHP / Apache.

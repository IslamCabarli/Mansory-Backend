use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Mansory API",
    version: "1.0.0",
    description: "Avtomobil satış sistemi üçün API sənədləşməsi"
)]
#[OA\Server(
    url: 'https://mansory-backend-production.up.railway.app/api',
    description: "Default Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class Controller extends \Illuminate\Routing\Controller
{
    // ...
}
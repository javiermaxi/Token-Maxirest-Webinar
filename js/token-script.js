document.addEventListener("DOMContentLoaded", function () {
    const isInductores = window.location.pathname.includes("/inductores");
    const isIntegraciones = window.location.pathname.includes("/integraciones");
    const isIntegracionesTest = window.location.pathname.includes("/test-form-integracion-partner");
    const MercadoPago = window.location.pathname.includes("/mercadopago");
    const Webinar = window.location.pathname.includes("/demo");

    const isAllowedPage = isInductores || isIntegraciones || isIntegracionesTest || MercadoPago || Webinar;

    if (isAllowedPage) {
        // Si estamos en la página 'inductores' y no existe el token, lo pedimos
        if (!sessionStorage.getItem('tokenAuth')) {
            fetch(ajax_object.ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "get_encrypted_token"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    sessionStorage.setItem('tokenAuth', data.data);
                  
                } else {
                    console.error('Error en respuesta:', data);
                }
            })
            .catch(error => {
                console.error('Fallo en la solicitud AJAX:', error);
            });
        } else {
            console.log('Token ya presente en sessionStorage');
        }
    } else {
        // Si NO estamos en 'inductores', borramos el token
        if (sessionStorage.getItem('tokenAuth')) {
            sessionStorage.removeItem('tokenAuth');
            console.log('Token eliminado del sessionStorage por cambio de página');
        }
    }
});

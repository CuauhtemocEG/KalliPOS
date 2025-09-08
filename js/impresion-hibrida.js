/**
 * Sistema de Impresión Híbrido - USB ESC/POS + Navegador
 * Combina impresión directa USB para entorno local y navegador para remoto
 */

class SistemaImpresionHibrido {
    constructor() {
        this.baseUrlUSB = 'controllers/imprimir_local_usb.php';
        this.baseUrlNavegador = 'controllers/ticket_local.php';
        this.entornoLocal = false;
        this.impresorasUSB = [];
        this.impresoraSeleccionada = null;
    }

    /**
     * Inicializar el sistema de impresión
     */
    async inicializar() {
        try {
            // Primero intentar detectar si estamos en entorno local
            await this.detectarEntorno();
            
            if (this.entornoLocal) {
                // Si estamos en local, detectar impresoras USB
                await this.detectarImpresorasUSB();
                console.log('✅ Sistema USB inicializado', this.impresorasUSB);
            } else {
                console.log('ℹ️ Entorno remoto detectado, usando sistema de navegador');
            }
            
            return {
                entornoLocal: this.entornoLocal,
                impresorasUSB: this.impresorasUSB,
                sistemaDisponible: true
            };
            
        } catch (error) {
            console.warn('⚠️ Error al inicializar sistema USB, usando fallback:', error);
            return {
                entornoLocal: false,
                impresorasUSB: [],
                sistemaDisponible: true,
                error: error.message
            };
        }
    }

    /**
     * Detectar si estamos en entorno local
     */
    async detectarEntorno() {
        try {
            const response = await fetch(this.baseUrlUSB, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo: 'detectar_impresoras' })
            });
            
            const data = await response.json();
            this.entornoLocal = data.success && data.entorno_local;
            
        } catch (error) {
            this.entornoLocal = false;
        }
    }

    /**
     * Detectar impresoras USB disponibles
     */
    async detectarImpresorasUSB() {
        if (!this.entornoLocal) return [];

        try {
            const response = await fetch(this.baseUrlUSB, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo: 'detectar_impresoras' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.impresorasUSB = data.impresoras;
                
                // Seleccionar automáticamente la primera impresora activa
                const impresoraActiva = this.impresorasUSB.find(imp => imp.activa);
                if (impresoraActiva) {
                    this.impresoraSeleccionada = impresoraActiva.nombre;
                }
                
                return this.impresorasUSB;
            } else {
                throw new Error(data.error || 'Error al detectar impresoras');
            }
            
        } catch (error) {
            console.error('Error detectando impresoras USB:', error);
            return [];
        }
    }

    /**
     * Seleccionar impresora USB
     */
    seleccionarImpresora(nombreImpresora) {
        const impresora = this.impresorasUSB.find(imp => imp.nombre === nombreImpresora);
        if (impresora) {
            this.impresoraSeleccionada = nombreImpresora;
            return true;
        }
        return false;
    }

    /**
     * Imprimir ticket de prueba
     */
    async imprimirPrueba() {
        if (this.entornoLocal && this.impresoraSeleccionada) {
            // Usar sistema USB
            return await this.imprimirPruebaUSB();
        } else {
            // Usar sistema de navegador
            return await this.imprimirPruebaNavegador();
        }
    }

    /**
     * Imprimir ticket de orden
     */
    async imprimirTicketOrden(ordenId) {
        if (this.entornoLocal && this.impresoraSeleccionada) {
            // Usar sistema USB
            return await this.imprimirTicketOrdenUSB(ordenId);
        } else {
            // Usar sistema de navegador
            return await this.imprimirTicketOrdenNavegador(ordenId);
        }
    }

    /**
     * Imprimir prueba via USB
     */
    async imprimirPruebaUSB() {
        try {
            const response = await fetch(this.baseUrlUSB, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo: 'prueba',
                    impresora: this.impresoraSeleccionada
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return {
                    success: true,
                    mensaje: `✅ Ticket enviado a impresora USB: ${this.impresoraSeleccionada}`,
                    metodo: 'USB/ESC-POS'
                };
            } else {
                throw new Error(data.error);
            }
            
        } catch (error) {
            // Si falla USB, intentar con navegador como fallback
            console.warn('USB falló, usando fallback navegador:', error);
            return await this.imprimirPruebaNavegador();
        }
    }

    /**
     * Imprimir ticket de orden via USB
     */
    async imprimirTicketOrdenUSB(ordenId) {
        try {
            const response = await fetch(this.baseUrlUSB, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo: 'ticket',
                    orden_id: ordenId,
                    impresora: this.impresoraSeleccionada
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return {
                    success: true,
                    mensaje: `✅ Ticket de orden #${ordenId} enviado a impresora USB: ${this.impresoraSeleccionada}`,
                    metodo: 'USB/ESC-POS'
                };
            } else {
                throw new Error(data.error);
            }
            
        } catch (error) {
            // Si falla USB, intentar con navegador como fallback
            console.warn('USB falló, usando fallback navegador:', error);
            return await this.imprimirTicketOrdenNavegador(ordenId);
        }
    }

    /**
     * Imprimir prueba via navegador
     */
    async imprimirPruebaNavegador() {
        const url = `${this.baseUrlNavegador}?tipo=prueba`;
        const ventana = window.open(url, '_blank', 'width=400,height=600,scrollbars=yes');
        
        if (ventana) {
            return {
                success: true,
                mensaje: '✅ Ticket de prueba abierto en navegador',
                metodo: 'Navegador'
            };
        } else {
            throw new Error('No se pudo abrir ventana de impresión. Verifica que no estén bloqueadas las ventanas emergentes.');
        }
    }

    /**
     * Imprimir ticket de orden via navegador
     */
    async imprimirTicketOrdenNavegador(ordenId) {
        const url = `${this.baseUrlNavegador}?orden_id=${ordenId}`;
        const ventana = window.open(url, '_blank', 'width=400,height=600,scrollbars=yes');
        
        if (ventana) {
            return {
                success: true,
                mensaje: `✅ Ticket de orden #${ordenId} abierto en navegador`,
                metodo: 'Navegador'
            };
        } else {
            throw new Error('No se pudo abrir ventana de impresión. Verifica que no estén bloqueadas las ventanas emergentes.');
        }
    }

    /**
     * Mostrar diálogo de configuración de impresora
     */
    mostrarConfiguracionImpresora() {
        if (!this.entornoLocal || this.impresorasUSB.length === 0) {
            alert('Sistema USB no disponible. Se usará impresión por navegador.');
            return;
        }

        let mensaje = 'Selecciona tu impresora térmica:\n\n';
        this.impresorasUSB.forEach((impresora, index) => {
            const estado = impresora.activa ? '✅ Activa' : '❌ Inactiva';
            const seleccionada = impresora.nombre === this.impresoraSeleccionada ? ' (SELECCIONADA)' : '';
            mensaje += `${index + 1}. ${impresora.nombre} - ${estado}${seleccionada}\n`;
        });

        mensaje += '\nEscribe el número de la impresora:';
        
        const seleccion = prompt(mensaje);
        const indice = parseInt(seleccion) - 1;
        
        if (indice >= 0 && indice < this.impresorasUSB.length) {
            this.seleccionarImpresora(this.impresorasUSB[indice].nombre);
            alert(`✅ Impresora seleccionada: ${this.impresoraSeleccionada}`);
        }
    }

    /**
     * Obtener estado del sistema
     */
    obtenerEstado() {
        return {
            entornoLocal: this.entornoLocal,
            impresorasUSB: this.impresorasUSB,
            impresoraSeleccionada: this.impresoraSeleccionada,
            metodoPreferido: this.entornoLocal && this.impresoraSeleccionada ? 'USB/ESC-POS' : 'Navegador'
        };
    }
}

// Instancia global
window.sistemaImpresion = new SistemaImpresionHibrido();

// Funciones de compatibilidad para código existente
window.imprimirTicketLocal = async function(ordenId) {
    try {
        const resultado = await window.sistemaImpresion.imprimirTicketOrden(ordenId);
        
        if (resultado.success) {
            console.log(resultado.mensaje);
            
            // Mostrar notificación visual
            mostrarNotificacion(resultado.mensaje, 'success');
        } else {
            throw new Error(resultado.error || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('Error al imprimir ticket:', error);
        mostrarNotificacion(`❌ Error: ${error.message}`, 'error');
    }
};

window.imprimirPruebaLocal = async function() {
    try {
        const resultado = await window.sistemaImpresion.imprimirPrueba();
        
        if (resultado.success) {
            console.log(resultado.mensaje);
            mostrarNotificacion(resultado.mensaje, 'success');
        } else {
            throw new Error(resultado.error || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('Error al imprimir prueba:', error);
        mostrarNotificacion(`❌ Error: ${error.message}`, 'error');
    }
};

window.configurarImpresora = function() {
    window.sistemaImpresion.mostrarConfiguracionImpresora();
};

// Función auxiliar para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 400px;
        font-family: Arial, sans-serif;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
    
    // Aplicar estilo según el tipo
    switch (tipo) {
        case 'success':
            notificacion.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notificacion.style.backgroundColor = '#dc3545';
            break;
        default:
            notificacion.style.backgroundColor = '#007bff';
    }
    
    notificacion.textContent = mensaje;
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.parentNode.removeChild(notificacion);
        }
    }, 5000);
}

// Inicializar sistema al cargar la página
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🖨️ Inicializando sistema de impresión híbrido...');
    
    try {
        const estado = await window.sistemaImpresion.inicializar();
        console.log('✅ Sistema inicializado:', estado);
        
        // Mostrar información en consola
        if (estado.entornoLocal) {
            console.log(`🔌 Entorno local detectado - ${estado.impresorasUSB.length} impresora(s) USB disponible(s)`);
        } else {
            console.log('🌐 Entorno remoto detectado - Usando impresión por navegador');
        }
        
    } catch (error) {
        console.warn('⚠️ Error en inicialización:', error);
    }
});

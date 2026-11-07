{{--
    Panel de detalle de Pedido / Venta / Compra.
    - Web: panel lateral morado (id, estados, datos, total) + lista de productos.
    - Móvil: hoja inferior.
    Uso:  <x-detalle-panel var="verPedido" />
    y en el botón:  @click="verPedido = @js($pedido->detalleVista())"
--}}
@props(['var'])

<div x-show="{{ $var }}" x-cloak
     @keydown.escape.window="{{ $var }} = null"
     @click.self="{{ $var }} = null"
     class="dp-overlay">
    <template x-if="{{ $var }}">
        <div class="dp-card" role="dialog" aria-modal="true"
             :aria-label="{{ $var }}.tipo + ' #' + {{ $var }}.id">

            {{-- Panel lateral (web) / cabecera + datos + total (móvil) --}}
            <div class="dp-side">
                <div class="dp-head">
                    <div class="dp-title">
                        <span class="dp-tipo" x-text="{{ $var }}.tipo"></span>
                        <span class="dp-id" x-text="'#' + {{ $var }}.id"></span>
                    </div>
                    <span class="dp-badge" :class="'dp-badge--' + {{ $var }}.estado.tono" x-text="{{ $var }}.estado.texto"></span>
                </div>

                <ol class="dp-pasos">
                    <template x-for="(paso, i) in {{ $var }}.pasos" :key="i">
                        <li class="dp-paso" :class="'dp-paso--' + paso.tono">
                            <span class="dp-paso-dot"></span>
                            <span class="dp-paso-texto" x-text="paso.texto"></span>
                            <span class="dp-paso-fecha" x-show="paso.fecha" x-text="paso.fecha"></span>
                        </li>
                    </template>
                </ol>

                <div class="dp-datos" x-show="{{ $var }}.datos.length">
                    <template x-for="(dato, i) in {{ $var }}.datos" :key="i">
                        <div class="dp-dato">
                            <i class="bi" :class="dato.icono"></i>
                            <div>
                                <span class="dp-dato-etiqueta" x-text="dato.etiqueta"></span>
                                <span class="dp-dato-valor" x-text="dato.valor"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="dp-total">
                    <span>Total</span>
                    <strong x-text="Number({{ $var }}.total).toFixed(2) + ' Bs.'"></strong>
                </div>
            </div>

            {{-- Productos --}}
            <div class="dp-main">
                <div class="dp-main-head">
                    <h3>Productos <span x-text="'· ' + {{ $var }}.items.length"></span></h3>
                    <button type="button" class="dp-x" @click="{{ $var }} = null" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="dp-items">
                    <template x-for="(it, i) in {{ $var }}.items" :key="i">
                        <div class="dp-item">
                            <template x-if="it.imagen">
                                <img class="dp-thumb" :src="it.imagen" :alt="it.nombre" loading="lazy">
                            </template>
                            <template x-if="!it.imagen">
                                <span class="dp-thumb dp-thumb--vacio"><i class="bi bi-image"></i></span>
                            </template>

                            <div class="dp-item-info">
                                <span class="dp-item-nombre" x-text="it.nombre"></span>
                                <span class="dp-item-precio" x-text="Number(it.precio).toFixed(2) + ' Bs.'"></span>
                            </div>

                            <div class="dp-item-der">
                                <span class="dp-qty" x-text="'x' + it.cantidad"></span>
                                <strong class="dp-item-sub" x-text="Number(it.subtotal).toFixed(2) + ' Bs.'"></strong>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" class="dp-cerrar" @click="{{ $var }} = null">Cerrar</button>
            </div>
        </div>
    </template>
</div>

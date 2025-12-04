<style>
.accordion { margin-top: 2rem; }
.accordion-item { border: 1px solid var(--divider); border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
.accordion-header { background: var(--off-white-light); padding: 15px; cursor: pointer; font-weight: 600; display: flex; justify-content: space-between; }
.accordion-content { display: none; padding: 15px; background: var(--white); color: var(--text-color); }
.accordion-header.active + .accordion-content { display: block; }
</style>

<div class="accordion">
    <div class="accordion-item">
        <div class="accordion-header">¿Cómo funcionan los envíos de aves? <i class="fas fa-chevron-down"></i></div>
        <div class="accordion-content">Realizamos envíos aéreos a los aeropuertos principales. El costo depende de la zona (Normal o Extendida).</div>
    </div>
    <div class="accordion-item">
        <div class="accordion-header">¿Qué métodos de pago aceptan? <i class="fas fa-chevron-down"></i></div>
        <div class="accordion-content">Aceptamos transferencia bancaria y depósitos en OXXO. Al finalizar tu pedido te enviaremos los datos por WhatsApp.</div>
    </div>
</div>

<script>
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
        header.classList.toggle('active');
        const icon = header.querySelector('i');
        icon.classList.toggle('fa-chevron-up');
        icon.classList.toggle('fa-chevron-down');
    });
});
</script>
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.value = 19;
    // Déclenche l'événement 'input' ou 'change' pour que Vue/Alpine/Livewire détecte la modif
    input.dispatchEvent(new Event('input'));
});

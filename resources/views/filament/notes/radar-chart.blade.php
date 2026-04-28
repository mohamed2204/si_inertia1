<div
    class="p-4 bg-white dark:bg-gray-800 rounded-xl flex flex-col items-center"
    x-data="{
        chart: null,
        init() {
            const render = () => {
                const data = @js($data);

                // CE PLUGIN EST LA CLÉ : Il dessine le fond BLANC dans le fichier PNG
                const backgroundColorPlugin = {
                    id: 'customCanvasBackgroundColor',
                    beforeDraw: (chart) => {
                        const {ctx} = chart;
                        ctx.save();
                        ctx.globalCompositeOperation = 'destination-over';
                        ctx.fillStyle = 'white'; // On force le blanc ici
                        ctx.fillRect(0, 0, chart.width, chart.height);
                        ctx.restore();
                    }
                };

                this.chart = new Chart(this.$refs.canvas, {
                    type: 'radar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Élève',
                                data: data.eleve,
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                borderColor: 'rgb(255, 99, 132)',
                            },
                            {
                                label: 'Moyenne Classe',
                                data: data.classe,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgb(54, 162, 235)',
                                borderDash: [5, 5]
                            }
                        ]
                    },
                    options: {
                        // IMPORTANT pour l'export : forcer les couleurs des textes en sombre
                        scales: {
                            r: {
                                angleLines: { color: '#e5e7eb' },
                                grid: { color: '#e5e7eb' },
                                pointLabels: { color: '#111827', font: { size: 12, weight: 'bold' } },
                                ticks: { color: '#111827', backdropColor: 'transparent' },
                                suggestedMin: 0,
                                suggestedMax: 20
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#111827' }
                            }
                        }
                    },
                    plugins: [backgroundColorPlugin] // On enregistre le plugin ici
                        });
            };

            // Chargement sécurisé du script local
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = '{{ asset('js/chart.js') }}';
                script.onload = render;
                document.head.appendChild(script);
            } else {
                render();
            }
        },
        downloadRadar() {
            if (!this.chart) return;
            const link = document.createElement('a');
            link.download = 'radar-performance.png';
            link.href = this.$refs.canvas.toDataURL('image/png');
            link.click();
        }
    }"
>
    <div class="w-full">
        <canvas x-ref="canvas"></canvas>
    </div>

    <button
        type="button"
        @click="downloadRadar()"
        class="mt-4 inline-flex items-center px-4 py-2 rounded-lg transition shadow-sm text-sm font-medium text-white"
        style="background-color: #4f46e5;"
        onmouseover="this.style.backgroundColor='#4338ca'"
        onmouseout="this.style.backgroundColor='#4f46e5'"
    >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Télécharger le radar (PNG)
    </button>
</div>

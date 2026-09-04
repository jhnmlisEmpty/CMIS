import './bootstrap';

document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardCalendar', (events, birthdays, initialYear, initialMonth) => ({
        year: initialYear,
        month: initialMonth,
        events,
        birthdays,
        get monthLabel() {
            return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date(this.year, this.month, 1));
        },
        get days() {
            const first = new Date(this.year, this.month, 1);
            const startOffset = (first.getDay() + 6) % 7;
            const totalDays = new Date(this.year, this.month + 1, 0).getDate();
            const today = new Date();
            const days = [];

            for (let index = 0; index < 42; index++) {
                const dayOffset = index - startOffset + 1;
                const date = new Date(this.year, this.month, dayOffset);
                const inMonth = dayOffset > 0 && dayOffset <= totalDays;
                const dateKey = this.formatDate(date);
                const birthdayKey = `${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                const items = [
                    ...this.events.filter(event => event.date === dateKey).map(event => ({ ...event, label: event.title, type: 'event-item', key: `event-${event.id}` })),
                    ...this.birthdays.filter(member => member.date === birthdayKey).map(member => ({ label: `${member.name}'s birthday`, url: member.url, type: 'birthday-item', key: `birthday-${member.id}` })),
                ];

                days.push({
                    key: dateKey,
                    number: date.getDate(),
                    inMonth,
                    isToday: date.getFullYear() === today.getFullYear() && date.getMonth() === today.getMonth() && date.getDate() === today.getDate(),
                    items,
                });
            }

            return days;
        },
        formatDate(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        },
        previousMonth() {
            if (this.month === 0) {
                this.month = 11;
                this.year--;
            } else {
                this.month--;
            }
        },
        nextMonth() {
            if (this.month === 11) {
                this.month = 0;
                this.year++;
            } else {
                this.month++;
            }
        },
        goToToday() {
            const today = new Date();
            this.year = today.getFullYear();
            this.month = today.getMonth();
        },
    }));
});

const attendanceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#173f35',
            padding: 10,
            displayColors: false,
            callbacks: {
                label: context => `${context.parsed.y} check-in${context.parsed.y === 1 ? '' : 's'}`,
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1, color: '#687069' },
            grid: { color: 'rgba(23, 63, 53, .08)' },
            border: { display: false },
        },
        x: {
            ticks: { color: '#687069' },
            grid: { display: false },
            border: { display: false },
        },
    },
};

const renderAttendanceCharts = () => {
    if (typeof window.Chart === 'undefined') return;

    document.querySelectorAll('canvas[data-attendance-chart]').forEach(canvas => {
        if (canvas.dataset.ready) return;

        const chartData = JSON.parse(atob(canvas.dataset.attendanceChart));
        canvas.dataset.ready = 'true';
        new window.Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    borderColor: '#286653',
                    backgroundColor: 'rgba(40, 102, 83, .08)',
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fcfcf9',
                    pointBorderColor: '#286653',
                    pointBorderWidth: 2,
                    tension: .35,
                }],
            },
            options: attendanceChartOptions,
        });
    });
};

let attendanceChartLoader;

const loadAttendanceChart = () => {
    if (!document.querySelector('canvas[data-attendance-chart]')) return;

    if (typeof window.Chart !== 'undefined') {
        renderAttendanceCharts();
        return;
    }

    if (attendanceChartLoader) return;

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = renderAttendanceCharts;
    script.onerror = () => console.error('Unable to load Chart.js for attendance charts.');
    attendanceChartLoader = script;
    document.head.appendChild(script);
};

document.addEventListener('DOMContentLoaded', loadAttendanceChart);
document.addEventListener('livewire:navigated', loadAttendanceChart);

// EditorJS
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Paragraph from '@editorjs/paragraph';
import Quote from '@editorjs/quote';
import Delimiter from '@editorjs/delimiter';
import Marker from '@editorjs/marker';
import InlineCode from '@editorjs/inline-code';

window.EditorJS = EditorJS;
window.EditorJSTools = {
    header: {
        class: Header,
        inlineToolbar: true,
        config: {
            levels: [2, 3, 4],
            defaultLevel: 2
        }
    },
    list: {
        class: List,
        inlineToolbar: true
    },
    paragraph: {
        class: Paragraph,
        inlineToolbar: true
    },
    quote: {
        class: Quote,
        inlineToolbar: true
    },
    delimiter: Delimiter,
    marker: {
        class: Marker,
        shortcut: 'CMD+SHIFT+M'
    },
    inlineCode: {
        class: InlineCode,
        shortcut: 'CMD+SHIFT+C'
    }
};

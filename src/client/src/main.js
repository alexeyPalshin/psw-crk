import './assets/main.css';
import 'primeicons/primeicons.css';

import {createApp} from 'vue';
import App from './App.vue';

import axios from './services/api';

// PrimeVue Imports
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';

const app = createApp(App);

app.provide('$axios', axios);

app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.my-app-dark',
        }
    }
});
app.use(ToastService);

app.mount('#app');

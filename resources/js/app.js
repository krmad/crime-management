import Alpine from 'alpinejs';
import { registerIncidentMapComponents } from './incident-map.js';

window.Alpine = Alpine;

registerIncidentMapComponents(Alpine);

Alpine.start();

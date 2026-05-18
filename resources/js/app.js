import './bootstrap';

import 'bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
import $ from 'jquery';
import Swal from 'sweetalert2';

window.$ = window.jQuery = $;
window.ApexCharts = ApexCharts;
window.DataTable = DataTable;
window.JSZip = JSZip;
window.pdfMake = pdfMake;
window.Swal = Swal;
window.Alpine = Alpine;

pdfMake.vfs = pdfFonts.vfs;

Alpine.start();

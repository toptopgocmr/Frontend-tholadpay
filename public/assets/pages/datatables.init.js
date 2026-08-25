/*
 Template Name: Veltrix - Responsive Bootstrap 4 Admin Dashboard
 Author: Themesbrand
 File: Datatable js
 */

$(document).ready(function() {
    $('#datatable').DataTable();

    //Buttons examples
    // FIX (2026-08-25, demande explicite : "laisser aussi le total figurer" dans
    // l'export Excel des transactions) : Buttons for DataTables 1.5.2 (version
    // utilisée ici, voir dataTables.buttons.min.js) n'inclut PAS le <tfoot> dans
    // les exports par défaut (ce comportement n'est devenu la valeur par défaut
    // qu'à partir de Buttons 3, cf. doc officielle datatables.net/extensions/
    // buttons/examples/html5/footer.html). 'excel' (raccourci pour 'excelHtml5')
    // remplacé par un objet explicite avec footer:true pour que la ligne TOTAL
    // (voir <tfoot> dans transactions/index.blade.php) apparaisse bien dans le
    // fichier Excel téléchargé. Sans effet sur les autres tables partageant ce
    // même fichier JS : footer:true n'a aucun effet si la table n'a pas de
    // <tfoot> (aucune des autres pages utilisant #datatable-buttons n'en a).
    var table = $('#datatable-buttons').DataTable({
        lengthChange: false,
        buttons: ['copy', {extend: 'excelHtml5', footer: true}, 'pdf', 'colvis']
    });

    table.buttons().container()
        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
} );
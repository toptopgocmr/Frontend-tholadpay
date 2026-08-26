/*
 Template Name: Veltrix - Responsive Bootstrap 4 Admin Dashboard
 Author: Themesbrand
 File: Datatable js
 */

$(document).ready(function() {
    $('#datatable').DataTable();

    //Buttons examples
    // FIX (2026-08-26, demande explicite : "retirer les total, total") : revert
    // du footer:true ajoute le 2026-08-25 -- Buttons pour DataTables 1.5.2 n'a
    // aucune notion de colspan lors de l'export du <tfoot> : la ligne TOTAL
    // (colspan="6" sur les colonnes texte, voir <tfoot> dans transactions/
    // index.blade.php) se retrouvait avec "TOTAL" recopie tel quel dans CHAQUE
    // colonne du groupe au lieu d'une seule cellule fusionnee -- illisible dans
    // le fichier Excel telecharge. Le <tfoot> reste affiche a l'ecran (toujours
    // utile en parcourant la liste), simplement plus inclus dans l'export.
    // Sans effet sur les autres tables partageant ce meme fichier JS (aucune
    // des autres pages utilisant #datatable-buttons n'a de <tfoot>).
    var table = $('#datatable-buttons').DataTable({
        lengthChange: false,
        buttons: ['copy', {extend: 'excelHtml5', footer: false}, 'pdf', 'colvis']
    });

    table.buttons().container()
        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
} );
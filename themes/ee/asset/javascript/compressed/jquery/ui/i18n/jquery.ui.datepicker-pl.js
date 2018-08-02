/* Polish initialisation for the jQuery UI date picker plugin. */
/* Written by Jacek Wysocki (jacek.wysocki@gmail.com). */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["../datepicker"],e):
// Browser globals
e(jQuery.datepicker)}(function(e){return e.regional.pl={closeText:"Zamknij",prevText:"&#x3C;Poprzedni",nextText:"Następny&#x3E;",currentText:"Dziś",monthNames:["Styczeń","Luty","Marzec","Kwiecień","Maj","Czerwiec","Lipiec","Sierpień","Wrzesień","Październik","Listopad","Grudzień"],monthNamesShort:["Sty","Lu","Mar","Kw","Maj","Cze","Lip","Sie","Wrz","Pa","Lis","Gru"],dayNames:["Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota"],dayNamesShort:["Nie","Pn","Wt","Śr","Czw","Pt","So"],dayNamesMin:["N","Pn","Wt","Śr","Cz","Pt","So"],weekHeader:"Tydz",dateFormat:"dd.mm.yy",firstDay:1,isRTL:!1,showMonthAfterYear:!1,yearSuffix:""},e.setDefaults(e.regional.pl),e.regional.pl});
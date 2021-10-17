/*
 * Specific Javascript file associated to stats.php
 */
Highcharts.setOptions({
    lang: {
            loading: 'Chargement...',
            months: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
            weekdays: ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
            shortMonths: ['jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'aoû', 'sep', 'oct', 'nov', 'déc'],
            exportButtonTitle: "Exporter",
            printButtonTitle: "Imprimer",
            rangeSelectorFrom: "Du",
            rangeSelectorTo: "au",
            rangeSelectorZoom: "Période",
            downloadPNG: 'Télécharger en PNG',
            downloadJPEG: 'Télécharger en JPEG',
            downloadPDF: 'Télécharger en PDF',
            downloadSVG: 'Télécharger en SVG',
            resetZoom: "Réinitialiser le zoom",
            resetZoomTitle: "Réinitialiser le zoom",
            thousandsSep: " ",
            decimalPoint: ',' 
        }        
});

//$.getJSON('aapl-c.json', function (data) {
$.getJSON('../scripts/server_processing_stats.php', function (data) {
    
    // split the data set
    var resaM = [], resaS = [], relM = [], relS = [], dataLength = data.length, i = 0;

    for (i; i < dataLength; i += 1) {
        resaM.push([ data[i][0], data[i][1] ]);
		resaS.push([ data[i][0], data[i][2] ]);
		relM.push([  data[i][0], data[i][3] ]);
		relS.push([  data[i][0], data[i][4] ]);
    }
	
	// Create the chart
    Highcharts.stockChart('container', {

        chart: {
			height: 550,
		},

        mapNavigation: {
            enableMouseWheelZoom: true
        },
		
		tooltip: {
			xDateFormat: '%d/%m/%Y',
			shared: true
		},
		
		xAxis: {
			dateTimeLabelFormats: {
				millisecond: '%H:%M:%S.%L',
				second: '%H:%M:%S',
				minute: '%H:%M',
				hour: '%H:%M',
				day: '%e %b',
				week: '%e %b',
				month: '%b %y',
				year: '%Y'
			},
			plotLines: [{
				color: '#8a8a8a',
				width: 4,
				value: new Date().getTime(),
			}]
		},
		
        yAxis: [{
            labels: { align: 'right', x: -3 },
            title: {
				text: 'Soir/APM',
				style: { fontWeight: 'bold', fontSize: '14', fontColor: '#000000' },
			},
            height: '60%',
            lineWidth: 2,
            resize: { enabled: true },
        }, {
            labels: { align: 'right', x: -3 },
            title: {
				text: 'Matin',
				style: { fontWeight: 'bold', fontSize: '14', fontColor: '#000000' },
			},
            top: '65%',
            height: '35%',
            offset: 0,
            lineWidth: 2
        }],

        rangeSelector: {
            selected: 1
        },

        title: {
            text: null
        },
		
		navigator: {		
			xAxis: {
				dateTimeLabelFormats: {
					millisecond: '%H:%M:%S.%L',
					second: '%H:%M:%S',
					minute: '%H:%M',
					hour: '%H:%M',
					day: '%e %b',
					week: '%e %b',
					month: '%b %y',
					year: '%Y'
				}
			},
			baseSeries: 3,
		},

        series: [{
            name: 'Réservation Matin',
            data: resaM,
			type: 'areaspline',
			yAxis: 1,
			tooltip: { valueDecimals: 0 },
            fillColor: {
                linearGradient: {
                    x1: 0,
                    y1: 0,
                    x2: 0,
                    y2: 1
                },
                stops: [
                    [0, Highcharts.getOptions().colors[0]],
                    [1, Highcharts.color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                ]
            },
		},{
            name: 'Réservation Soir/APM',
            data: resaS,
			type: 'areaspline',
			tooltip: { valueDecimals: 0 },
            fillColor: {
                linearGradient: {
                    x1: 0,
                    y1: 0,
                    x2: 0,
                    y2: 1
                },
                stops: [
                    [0, Highcharts.getOptions().colors[0]],
                    [1, Highcharts.color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
                ]
            },
		},{
            name: 'Présence Matin',
            data: relM,
			type: 'column',
			yAxis: 1,
            tooltip: { valueDecimals: 0 },
            dataLabels: {
                enabled: true,
                align: 'center',
                crop: false,
                style: { fontWeight: 'bold', },
                x: 0,
				formatter: function(){
					return (this.y != 0 ) ? this.y : "";
				},
                verticalAlign: 'bottom',
            },
		},{
            name: 'Présence Soir/APM',
            data: relS,
			type: 'column',
            tooltip: { valueDecimals: 0 },
            dataLabels: {
                enabled: true,
                align: 'center',
                crop: false,
                style: { fontWeight: 'bold', },
                x: 0,
				formatter: function(){
					return (this.y != 0 ) ? this.y : "";
				},
                verticalAlign: 'bottom',
            },
        }],
		legend: {
            enabled: true
        },
    });
});
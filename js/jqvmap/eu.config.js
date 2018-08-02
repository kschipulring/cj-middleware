var europe = [
  {
    'cat_id': 3635,
    'cat_url_title': 'eu',
    'cat_name': 'EU',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#F2F2EA',
    'number_of_stops': 7,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3424,
    'cat_url_title': 'united-kingdom',
    'cat_name': 'United Kingdom',
    'parent_id': 0,
    'parent_url_title': 'eu',
    'parent_name': 'Europe',
    'unlockorder': 1,
    'color': '#7c3842',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3645,
    'cat_url_title': 'germany',
    'cat_name': 'Germany',
    'parent_id': 0,
    'parent_url_title': 'eu',
    'parent_name': 'Europe',
    'unlockorder': 2,
    'color': '#e18a9f',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3643,
    'cat_url_title': 'france',
    'cat_name': 'France',
    'parent_id': 0,
    'parent_url_title': 'eu',
    'parent_name': 'Europe',
    'unlockorder': 3,
    'color': '#921f34',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3648,
    'cat_url_title': 'switzerland',
    'cat_name': 'Switzerland',
    'parent_id': 0,
    'parent_url_title': 'eu',
    'parent_name': 'Europe',
    'unlockorder': 3,
    'color': '#ac5f6f',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3656,
    'cat_url_title': 'italy',
    'cat_name': 'Italy',
    'parent_id': 0,
    'parent_url_title': 'eu',
    'parent_name': 'Europe',
    'unlockorder': 5,
    'color': '#c52528',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3639,
    'cat_url_title': 'spain',
    'cat_name': 'Spain',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 6,
    'color': '#906a46',
    'number_of_stops': '',
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3642,
    'cat_url_title': 'portugal',
    'cat_name': 'Portugal',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 7,
    'color': '#a3957e',
    'number_of_stops': '',
    'minutes_to_finish': ''
  }
]

var pin = "<div class='map-pin' >" +
  //"<svg class='pin-img'><use class='marker-icon' xlink:href='#marker' /></svg>" +
  "</div>";
var euConfig = {
	mapJavaScriptId: 'eu_map',
	mapInfo: europe,
	elanMapTag: elanTags.eu,
	elanMapObjectIdTag: 'EU',
	elanMapUnlockObjectIdTag: 'united-kingdom',
	darkColors: {
	  'bg': '#BED8F1',
    'eu': '#F2F2EA',
    'united-kingdom': '#47111D',
    'germany': '#532733',
		'france': '#46000B',
		'switzerland': '#52212D',
		'italy': '#600000',
		'spain': '#412C18',
		'portugal': '#494135',
    'france-border': '#F2F2EA',
    'portugal-border': '#F2F2EA',
    'france-spain-border': '#F2F2EA',
    'switzerland-border': '#F2F2EA'
	},
  originalColors: {
    'bg': '#BED8F1',
    'eu': '#F2F2EA',
    'united-kingdom': '#7c3842',
    'germany': '#e18a9f',
    'france': '#921f34',
		'switzerland': '#ac5f6f',
		'italy': '#c52528',
		'spain': '#906a46',
		'portugal': '#a3957e',
    'france-border': '#ffffff',
    'portugal-border': '#ffffff',
    'france-spain-border': '#ffffff',
    'switzerland-border': '#ffffff'
	},
	pins: {
		'united-kingdom': pin,
		'germany': pin,
		'france': pin,
		'switzerland': pin,
		'italy': pin,
		'spain': pin,
		'portugal': pin
	},
	additionalContent: [],
	selectedColors: this.originalColors,

	getRegionBorderColors: function(selectedArea) {
		var originalColors = this.originalColors;
		var darkColors = this.darkColors;
		var newColors = $.extend( {}, euConfig.selectedColors);
		// console.log('getRegionBorderColors for '+selectedArea+': '+ newColors[selectedArea], originalColors[selectedArea]);

    newColors['united-kingdom'] = darkColors['united-kingdom'];
    newColors['germany'] = darkColors['germany'];
    newColors['france'] = darkColors['france'];
    newColors['switzerland'] = darkColors['switzerland'];
    newColors['italy'] = darkColors['italy'];
    newColors['spain'] = darkColors['spain'];
    newColors['portugal'] = darkColors['portugal'];
    newColors['united-kingdom'] = darkColors['united-kingdom'];

    newColors['france-border'] = darkColors['france-border'];
    newColors['portugal-border'] = darkColors['france-spain-border'];
    newColors['france-spain-border'] = darkColors['france-spain-border'];
    newColors['switzerland-border'] = darkColors['switzerland-border'];

    newColors[selectedArea] = originalColors[selectedArea];

		switch( selectedArea ) {
			case 'united-kingdom':
				break;
      case 'germany':
        break;
      case 'france':
        newColors['france-border'] = originalColors['france-border'];
        newColors['france-spain-border'] = originalColors['france-spain-border'];
        break;
      case 'switzerland':
        newColors['switzerland-border'] = originalColors['switzerland-border'];
        break;
      case 'italy':
        break;
      case 'spain':
        newColors['france-spain-border'] = originalColors['france-spain-border'];
        break;
      case 'portugal':
        newColors['portugal-border'] = originalColors['portugal-border'];
        break;
		}		
		return newColors;	
	}
};
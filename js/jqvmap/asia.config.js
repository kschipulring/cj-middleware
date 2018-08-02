var asia = [
  {
    'cat_id': 3850,
    'cat_url_title': 'asia',
    'cat_name': 'Asia',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#F2F2EA',
    'number_of_stops': 7,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3851,
    'cat_name': 'Japan',
    'cat_url_title': 'japan',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#47697B',
    'number_of_stops': 3,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Sapporo',
    'cat_url_title': 'sapporo',
    'parent_id': 3851,
    'parent_url_title': 'japan',
    'parent_name': 'Jappan',
    'unlockorder': 0,
    'color': '#47697B',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Kyoto',
    'cat_url_title': 'kyoto',
    'parent_id': 3851,
    'parent_url_title': 'japan',
    'parent_name': 'Jappan',
    'unlockorder': 0,
    'color': '#47697B',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Tokyo',
    'cat_url_title': 'tokyo',
    'parent_id': 3851,
    'parent_url_title': 'japan',
    'parent_name': 'Jappan',
    'unlockorder': 0,
    'color': '#47697B',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3852,
    'cat_name': 'China',
    'cat_url_title': 'china',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#178f98',
    'number_of_stops': 4,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3963,
    'cat_name': 'Beijing',
    'cat_url_title': 'beijing',
    'parent_id': 3852,
    'parent_url_title': 'china',
    'parent_name': 'China',
    'unlockorder': 0,
    'color': '#178f98',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Chengdu',
    'cat_url_title': 'chengdu',
    'parent_id': 3852,
    'parent_url_title': 'china',
    'parent_name': 'China',
    'unlockorder': 0,
    'color': '#178f98',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Shanghai',
    'cat_url_title': 'shanghai',
    'parent_id': 3852,
    'parent_url_title': 'china',
    'parent_name': 'China',
    'unlockorder': 0,
    'color': '#178f98',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Hongkong',
    'cat_url_title': 'hongkong',
    'parent_id': 3852,
    'parent_url_title': 'china',
    'parent_name': 'China',
    'unlockorder': 0,
    'color': '#178f98',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3854,
    'cat_name': 'Korea',
    'cat_url_title': 'korea',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#236592',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3855,
    'cat_name': 'Taiwan',
    'cat_url_title': 'taiwan',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#7d7f7d',
    'number_of_stops': 3,
    'minutes_to_finish': ''
  },
  {
    'cat_id': 3853,
    'cat_name': 'South East Asia',
    'cat_url_title': 'south-east-asia',
    'parent_id': 0,
    'parent_url_title': '',
    'parent_name': '',
    'unlockorder': 0,
    'color': '#236592',
    'number_of_stops': 3,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Singapore',
    'cat_url_title': 'singapore',
    'parent_id': 3853,
    'parent_url_title': 'south-east-asia',
    'parent_name': 'South East Asia',
    'unlockorder': 0,
    'color': '#236592',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Malaysia',
    'cat_url_title': 'malaysia',
    'parent_id': 3853,
    'parent_url_title': 'south-east-asia',
    'parent_name': 'South East Asia',
    'unlockorder': 0,
    'color': '#236592',
    'number_of_stops': 0,
    'minutes_to_finish': ''
  },
  {
    'cat_id': '',
    'cat_name': 'Vietnam',
    'cat_url_title': 'vietnam',
    'parent_id': 3853,
    'parent_url_title': 'south-east-asia',
    'parent_name': 'South East Asia',
    'unlockorder': 0,
    'color': '#236592',
    'number_of_stops': 1,
    'minutes_to_finish': ''
  }
]

var pin = "<div class='map-pin' >" +
  "<svg class='pin-img'><use class='marker-icon' xlink:href='#marker' /></svg>" +
  "</div>";

var asiaConfig = {
  mapJavaScriptId: 'asia_map',
  mapInfo: asia,
  elanMapTag: elanTags.asia,
  elanMapObjectIdTag: 'Asia',
  elanMapUnlockObjectIdTag: 'china',
  darkColors: {
    'bg': '#BED8F1',
    'map-background': '#f3f2ea',
    'water': '#BED8F1',
    'asia': '#f3f2ea',

    'south-east-asia': '#133751',
    'malaysia': '#133751',
    'singapore': '#133751',
    'vietnam': '#133751',
    'hanoi': '#133751',

    'japan': '#283A44',
    'sapporo': '#283A44',
    'kyoto': '#283A44',
    'tokyo': '#283A44',

    'korea': '#305872',
    'seoul': '#305872',

    'taiwan': '#464646',
    'tapei': '#464646',

    'china': '#168F98',
    'shanghai': '#168F98',
    'beijing': '#168F98',
    'chengdu': '#168F98',
    'hongkong': '#168F98'
  },
  originalColors: {
    'bg': '#BED8F1',
    'map-background': '#f3f2ea',
    'water': '#BED8F1',
    'asia': '#f3f2ea',

    'south-east-asia': '#226592',
    'malaysia': '#226592',
    'singapore': '#226592',
    'vietnam': '#226592',
    'hanoi': '#226592',

    'japan': '#47697B',
    'sapporo': '#47697B',
    'kyoto': '#47697B',
    'tokyo': '#47697B',

    'korea': '#236592',
    'seoul': '#236592',

    'taiwan': '#7d7f7d',
    'tapei': '#7d7f7d',

    'china': '#0E4F53',
    'shanghai': '#0E4F53',
    'beijing': '#0E4F53',
    'chengdu': '#0E4F53',
    'hongkong': '#0E4F53'
  },
  pins: {
    //japan
    'sapporo': pin,
    'kyoto': pin,
    'tokyo': pin,

    //south-east-asia
    'malaysia': pin,
    'singapore': pin,
    'vietnam': pin,
    'hanoi': pin,

    //taiwan
    'tapei': pin,

    //china
    'shanghai': pin,
    'beijing': pin,
    'chengdu': pin,
    'hongkong': pin,

    //'korea': pin,
    'seoul': pin
  },
  additionalContent: [],
  selectedColors: this.originalColors,

  getRegionBorderColors: function (selectedArea) {
    var originalColors = this.originalColors;
    var darkColors = this.darkColors;
    var newColors = $.extend({}, asiaConfig.selectedColors);
    console.log('getRegionBorderColors', selectedArea);

    newColors['china'] = darkColors['china'];
    newColors['beijing'] = darkColors['beijing'];
    newColors['chengdu'] = darkColors['chengdu'];
    newColors['shanghai'] = darkColors['shanghai'];

    newColors['korea'] = darkColors['korea'];
    newColors['seoul'] = darkColors['seoul'];

    newColors['japan'] = darkColors['japan'];
    newColors['kyoto'] = darkColors['kyoto'];
    newColors['sapporo'] = darkColors['sapporo'];
    newColors['tokyo'] = darkColors['tokyo'];

    newColors['south-east-asia'] = darkColors['south-east-asia'];
    newColors['malaysia'] = darkColors['malaysia'];
    newColors['singapore'] = darkColors['singapore'];
    newColors['taiwan'] = darkColors['taiwan'];

    newColors['vietnam'] = darkColors['vietnam'];

    newColors[selectedArea] = originalColors[selectedArea];

    switch (selectedArea) {
      case 'china':
        break;
      case 'japan':
        break;
      case 'korea':
        break;
      case 'singapore':
        break;
      case 'south-east-asia':
        break;
      case 'taiwan':
        break;
      case 'vietnam':
        break;
    }
    return newColors;
  }
};


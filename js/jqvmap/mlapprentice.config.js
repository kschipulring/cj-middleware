var mlApprenticeConfig = {
	mapJavaScriptId: 'mlapprentice_map',
	mapInfo: mlapprentice,
	elanMapTag: elanTags.mlapprentice,
	elanMapObjectIdTag: 'Journey Main',
	elanMapUnlockObjectIdTag: 'Chinatown',
	originalColors: {
		"financialdistrict": "#F3F2EA",
		"st1": "#9CC0DC",
		"st2": "#BDD7EF",
		"chinatown": "#B1B0AE",
		"tribeca": "#CACAC8",
		"lowereastside": "#999792",
		"eastvillage": "#D3B4D6",
		"greenwichvillage": "#786578",
		"soho": "#997E99",
		"westvillage": "#A8A1CD",
		"thehighlineneighborhood": "#9C7C63",
		"chelsea": "#8683B0",
		"gramercy": "#A79CA7",
		"murrayhill": "#E3CA68",
		"garmentdistrict": "#FBE381",
		"hellskitchen": "#FAEEBF",
		"clinton": "#87B5CB",
		"columbuscircle": "#A9D7ED",
		"midtowneast": "#36718C",
		"theaterdistrict": "#5D91A9",
		"upperwestsideneighborhood": "#287547",
		"centralpark": "#4A9468",
		"uppereastside": "#98CEB3",
		"yorkville": "#CDEDDD",
		"st24l1": "#FFFFFF",
		"st24l2": "#FFFFFF",
		"st24l3": "#FFFFFF",
		"st24l4": "#FFFFFF",
		"st24l5": "#FFFFFF",
		"st24l6": "#FFFFFF",
		"st24l7": "#FFFFFF"
	},
	darkColors: {
		"financialdistrict": "#3d3d3b",
		"st1": "#9CC0DC",
		"st2": "#BDD7EF",
		"chinatown": "#2c2c2c",
		"tribeca": "#333333",
		"lowereastside": "#262626",
		"eastvillage": "#352d36",
		"greenwichvillage": "#1e191e",
		"soho": "#262026",
		"westvillage": "#2a2833",
		"thehighlineneighborhood": "#271f19",
		"chelsea": "#22212c",
		"gramercy": "#2a272a",
		"murrayhill": "#39331a",
		"garmentdistrict": "#3f3920",
		"hellskitchen": "#3f3c30",
		"clinton": "#222d33",
		"columbuscircle": "#2a363b",
		"midtowneast": "#0e1c23",
		"theaterdistrict": "#17242a",
		"upperwestsideneighborhood": "#0a1d12",
		"centralpark": "#13251a",
		"uppereastside": "#26342d",
		"yorkville": "#333b37",
		"st24l1": "#404040",
		"st24l2": "#404040",
		"st24l3": "#404040",
		"st24l4": "#404040",
		"st24l5": "#404040",
		"st24l6": "#404040",
		"st24l7": "#404040"
	},
	pins: {
		"financialdistrict": "<div class='map-pin'><span class='pin-img'></span></div>",
		"chinatown": "<div class='map-pin'><span class='pin-img'></span></div>",
		"tribeca": "<div class='map-pin'><span class='pin-img'></span></div>",
		"lowereastside": "<div class='map-pin'><span class='pin-img'></span></div>",
		"eastvillage": "<div class='map-pin'><span class='pin-img'></span></div>",
		"greenwichvillage": "<div class='map-pin'><span class='pin-img'></span></div>",
		"soho": "<div class='map-pin'><span class='pin-img'></span></div>",
		"westvillage": "<div class='map-pin'><span class='pin-img'></span></div>",
		"thehighlineneighborhood": "<div class='map-pin'><span class='pin-img'></span></div>",
		"chelsea": "<div class='map-pin'><span class='pin-img'></span></div>",
		"gramercy": "<div class='map-pin'><span class='pin-img'></span></div>",
		"murrayhill": "<div class='map-pin'><span class='pin-img'></span></div>",
		"garmentdistrict": "<div class='map-pin'><span class='pin-img'></span></div>",
		"hellskitchen": "<div class='map-pin'><span class='pin-img'></span></div>",
		"clinton": "<div class='map-pin'><span class='pin-img'></span></div>",
		"columbuscircle": "<div class='map-pin'><span class='pin-img'></span></div>",
		"midtowneast": "<div class='map-pin'><span class='pin-img'></span></div>",
		"theaterdistrict": "<div class='map-pin'><span class='pin-img'></span></div>",
		"upperwestsideneighborhood": "<div class='map-pin'><span class='pin-img'></span></div>",
		"centralpark": "<div class='map-pin'><span class='pin-img'></span></div>",
		"uppereastside": "<div class='map-pin'><span class='pin-img'></span></div>",
		"yorkville": "<div class='map-pin'><span class='pin-img'></span></div>"
	},
	additionalContent: [],
	selectedColors: this.originalColors,
	getRegionBorderColors: function(selectedArea) {
		var originalColors = this.originalColors;
		var darkColors = this.darkColors;
		var newColors = $.extend( {}, mlApprenticeConfig.selectedColors);
		//console.log(selectedArea);
		switch( selectedArea ) {
			case "lowermanhattan":
				newColors['st24l1'] = originalColors['st24l1'];
				newColors['st24l2'] = darkColors['st24l2'];
				newColors['st24l3'] = darkColors['st24l3'];
				newColors['st24l4'] = darkColors['st24l4'];
				newColors['st24l5'] = darkColors['st24l5'];
				newColors['st24l6'] = darkColors['st24l6'];
				newColors['st24l7'] = darkColors['st24l7'];
				break;
			case "thehighline":
				newColors['st24l1'] = darkColors['st24l1'];
				newColors['st24l2'] = originalColors['st24l2'];
				newColors['st24l3'] = originalColors['st24l3'];
				newColors['st24l4'] = darkColors['st24l4'];
				newColors['st24l5'] = originalColors['st24l5'];
				newColors['st24l6'] = darkColors['st24l6'];
				newColors['st24l7'] = darkColors['st24l7'];
				break;
			case "downtown":
				newColors['st24l1'] = originalColors['st24l1'];
				newColors['st24l2'] = originalColors['st24l2'];
				newColors['st24l3'] = originalColors['st24l3'];
				newColors['st24l4'] = originalColors['st24l4'];
				newColors['st24l5'] = darkColors['st24l5'];
				newColors['st24l6'] = darkColors['st24l6'];
				newColors['st24l7'] = darkColors['st24l7'];
				break;
			case "midtownsouth":
				newColors['st24l1'] = darkColors['st24l1'];
				newColors['st24l2'] = darkColors['st24l2'];
				newColors['st24l3'] = originalColors['st24l3'];
				newColors['st24l4'] = originalColors['st24l4'];
				newColors['st24l5'] = originalColors['st24l5'];
				newColors['st24l6'] = originalColors['st24l6'];
				newColors['st24l7'] = darkColors['st24l7'];						
				break;	
			case "midtown":
				newColors['st24l1'] = darkColors['st24l1'];
				newColors['st24l2'] = darkColors['st24l2'];
				newColors['st24l3'] = darkColors['st24l3'];
				newColors['st24l4'] = darkColors['st24l4'];
				newColors['st24l5'] = darkColors['st24l5'];
				newColors['st24l6'] = originalColors['st24l6'];
				newColors['st24l7'] = originalColors['st24l7'];						
				break;			
			case "uptown":
				newColors['st24l1'] = darkColors['st24l1'];
				newColors['st24l2'] = darkColors['st24l2'];
				newColors['st24l3'] = darkColors['st24l3'];
				newColors['st24l4'] = darkColors['st24l4'];
				newColors['st24l5'] = darkColors['st24l5'];
				newColors['st24l6'] = darkColors['st24l6'];
				newColors['st24l7'] = originalColors['st24l7'];						
				break;	
		}		
		return newColors;	
	}
};
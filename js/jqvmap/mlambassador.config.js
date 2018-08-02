var mlAmbssadorConfig = {
	mapJavaScriptId: 'mlambassador_map',
	mapInfo: mlambassador,
	elanMapTag: elanTags.mlambassador,
	elanMapObjectIdTag: 'Journey Main',
	elanMapUnlockObjectIdTag: '',
	originalColors: {},
	darkColors: {},
	pins: {},
	additionalContent: [],
	selectedColors: this.originalColors,
	getRegionBorderColors: function(selectedArea) {
		var originalColors = this.originalColors;
		var darkColors = this.darkColors;
		var newColors = $.extend( {}, mlAmbssadorConfig.selectedColors);
		switch( selectedArea ) {
			case 'areaname':
				break;
		}
		return newColors;
	}
};
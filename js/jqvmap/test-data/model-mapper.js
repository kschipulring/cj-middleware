var modelMapper = {
    objectsToEvaluate: {},
    //get/set methods
    getNestedArr: function() { return this.nestedArr },
    getMappedObj: function() { return this.mappedObj },
    setMappedObj: function(objIn)  { this.mappedObj = objIn; },
    setNestedArr: function(arrIn)  { this.nestedArr = arrIn; },
    //get the ajax data into a separate object that the oject can evaluate
    getNestedObjectsFromParent: function(parentObj, nestedArr)  {
        var objContainer = Object.create(modelMapper.objectsToEvaluate);
        for (var index in nestedArr)  {
            if(parentObj.hasOwnProperty(nestedArr[index]))  {
                var dataObj = parentObj[nestedArr[index]];
                modelMapper.objectsToEvaluate[nestedArr[index]] = dataObj;
            }
        }
    },
    init: function()  {
        this.getNestedObjectsFromParent(this.getMappedObj(),this.getNestedArr());
        console.log(this.objectsToEvaluate);
    }
};
$(function() {
    setTimeout(function()  {
        modelMapper.setMappedObj(globalDataInteractionObj.objectProperties);
        modelMapper.setNestedArr(['rawAjaxUserData', 'rawAjaxCourseData', 'rawAjaxCourseDetailData']);
        modelMapper.init();
    }, 100);
});
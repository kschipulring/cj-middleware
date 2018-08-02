'use strict';
/*
 *  Definition for the majority of interactions across the Coach Journey application, including the API calls that
 *  retrieve user and CLO data. This definition requires the following libraries to fully function:
 *
 *  1) jQuery - tested on v2.2.3
 *  2) Twitter Bootstrap - tested on v3.3.6
 *  3) Handlebars, runtime version - tested on v4.0.5
 */
var quiz = {};
var monthly_focus = {};
var g2 = {};
var freshMapObject = null;
var watchPopupClose = null; //Interval object
var idleInterval = null; //Interval object
var resizer = null; //Timeout object for window.resizevar
var resizer2 = null; //Timeout object for window.resize
monthly_focus.child = false;
monthly_focus.class_link = false;
monthly_focus.child_has_been_opened = false;
$('.course-detail').hide();

var globalDataInteractionObj = {
  /*
   *  The objectProperties declaration is meant to serve as a a configuration object for the rest of the script.
   *  Add/change as needed.
   */
  isMapLoaded: false,
  buttonsWired: false,
  objectProperties: {
    //force an index.php into the ajax template paths. Pull this out for production.
    forcePath: false,
    //properties defined by the platform
    isIPad: (navigator.userAgent.match(/iPad/i) != null),
    appRoot: site_url,
    appLogout: site_logout,
    apiRequestParam: exp_actions.elan_api_request,
    apiUserId: member_profile_data.elan_user_id,
    apiUserName: member_profile_data.elan_username,
    apiUserLanguage: { "id": 1, "name": "English", "tag": "English", "abbreviation": "en" },
    //if more handlebars templates are to be used, include the mappings here. The 'setTemplates' method in the
    //'handlebarsUtils' object reads this property.
    //mapping structure(key:value) - key = handlebars template id, value = view element class where template is rendered
    handlebarsDomMap: {
      'course-detail-overlay': 'course-detail',
      'course-data-summary-template': 'course-data-container',
      'map-info-template': 'map-info',
      'quiz-modal': 'quiz-container'
    },
    //this value will be overwritten, in theory, when all of the ajax content is loaded.
    ajaxContentHeightValue: 0,
    //catching and storing the viewport height into a variable for the opaque page overlay
    viewportHeight: document.documentElement.clientHeight,
    //this array will contain any element classes that should initialize the page level overlay.
    pageToggleElements: ['journey-main-menu', 'translator-icon', 'slider-top', 'slider-map'],
    //capture the ajax responses and store in an object property here
    rawAjaxUserData: null,
    rawAjaxCourseData: null,
    rawAjaxItineraryData: null,
    rawAjaxCourseDetailData: null,
    translations: null
  },
  /*  End configurations  */
  //container for utility methods that can be used across the definition (UI definitions)
  utilities: {
/*    //This may need to be revisited as native PHP methods handle date display as well.
    getCurrentMonth: function () {
      var currentMonthAsString;
      //TODO: clear the input parameter from the date before production deployment
      var localDate = new Date(),
        currentMonth = localDate.getMonth(),
        //this may need to be dynamically appended for region/language
      monthArray = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      for (var index in monthArray) {
        if (currentMonth == index) currentMonthAsString = monthArray[index];
      }
      currentMonthAsString = "october";
    },*/
    truncateString: function (string) {
      if (string.length > 37) {
        var trimmedString = string.substring(0, 37);
        return string.substr(0, Math.min(trimmedString.length, trimmedString.lastIndexOf(" "))) + '...';
      } else {
        return string;
      }
    },
    toProperCase: function (str) {
      var noCaps = ['of', 'a', 'the', 'and', 'an', 'am', 'or', 'nor', 'but', 'is', 'if', 'then',
        'else', 'when', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'to', 'into', 'with'];
      var capExceptions = ['slgs'];
      return str.replace(/\w\S*/g, function (txt, offset) {
        if (offset != 0 && noCaps.indexOf(txt.toLowerCase()) != -1) {
          return txt.toLowerCase();
        }
        if (offset != 0 && capExceptions.indexOf(txt.toLowerCase()) != -1) {
          return txt.toUpperCase();
        }
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
      });
    },
    createClassName: function(obj){
      var str = (typeof(obj) != 'string') ? obj.toString() : obj;
      return str.toLowerCase().trim().replace(/ /g, '-').replace(/,-/g, ' ');
    },
    getCountryCode: function() {
		return member_profile_data.country_code;
	},
	getLocationIP: function() {
		return member_profile_data.location_ip;
	},
	getCookie: function(cname) {
			var name = cname + "=";
			var ca = document.cookie.split(';');
			for(var i = 0; i <ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0)==' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) == 0) {
					return c.substring(name.length,c.length);
				}
			}
			return "";
		},
	/*
    getCookie: function (cname) {
      // //console.log("getCookie for "+cname, document.cookie);

      if(document.cookie) {
        var cookieString = String('{"' + document.cookie + '"}')
          .replace(/; /g, '","')
          .replace(/=/gm, '":"');
        var cookieJSON = JSON.parse(cookieString);
         //console.log("cookieJSON", cookieJSON);
        return cookieJSON[cname] || "";
      } else {
        if(cname == "exp_elan_user_id") {
          return globalDataInteractionObj.objectProperties.apiUserId;
        }
        if(cname == "exp_elan_username") {
          return globalDataInteractionObj.objectProperties.apiUserId;
        }
      }
      return "";
    },
	*/
    lang: function (key) {
      var translations = globalDataInteractionObj.objectProperties.translations;
      var text = key;
      if (translations && translations.hasOwnProperty(key)) {
        text = translations[key];
      }
      return text;
    },
    setAllowedLanguages: function (elementClass, bindLanguageLinks) {
      $.each(elanLanguages, function (key, langObject) {
        var langLink = '';
        if (langObject.id == 1) {
          langLink = '<a class="btn change-language selected" attr-lang-id="' + langObject.id + '" attr-lang-abbr="' + langObject.abbreviation + '">' + langObject.name + '</a>';
        } else {
          langLink = '<a class="btn change-language" attr-lang-id="' + langObject.id + '" attr-lang-abbr="' + langObject.abbreviation + '">' + langObject.name + '</a>';
        }
        $("div." + elementClass).append(langLink);
      });
      //console.log(typeof(bindLanguageLinks));
      if (typeof(bindLanguageLinks) == 'function') {
        bindLanguageLinks();
      }
    },
    //helper method to create a computational object to handle the calculations for circle circumferences and partials
    setProgressionCircleParameters: function (jqElemIn) {
      var circleObj = {};
      circleObj.elemRadius = parseInt(jqElemIn.attr('r'));
      circleObj.elemCircumference = Math.round(2 * (Math.PI) * circleObj.elemRadius);
      circleObj.elemRatio = circleObj.elemCircumference / 100;
      return circleObj;
    },
    escapeXml: function (string) {
      return string.replace(/[<>]/g, function (c) {
        switch (c) {
          case '<':
            return '\u003c';
          case '>':
            return '\u003e';
        }
      });
    },
    findIndexByProperty: function (records, propertyName, propertyValue) {
      return records.findIndex(function (element) {
        return element[propertyName] == propertyValue;
      });
    },
    findOneByProperty: function (records, propertyName, propertyValue, recordType) {
      if (records.length > 0) {
        if (typeof(recordType) != 'undefined' && recordType == 'AREA') {
          return records.filter(function (element) {
              return element[propertyName] == propertyValue && element['parent_id'] == 0;
            })[0] || null;
        } else if (typeof(recordType) != 'undefined' && recordType == 'NEIGHBORHOOD') {
          return records.filter(function (element) {
              return element[propertyName] == propertyValue && element['parent_id'] != 0;
            })[0] || null;
        } else {
          return records.filter(function (element) {
              return element[propertyName] == propertyValue;
            })[0] || null;
        }
      } else {
        return null;
      }
    },
    findAllByProperty: function (records, propertyName, propertyValue) {
      return records.filter(function (element) {
          return element[propertyName] == propertyValue;
        }) || null;
    },
    sortByProperty: function (records, propertyName) {
      return records.sort(function (a, b) {
          return a[propertyName] > b[propertyName];
        }) || null;
    },
    findJourneyMap: function (records, mapTagName) {
      return records.filter(function (r) {
          var tags = $.map(r['TAGS'].split(','), $.trim);
          return r['OBJECT_TYPE_ID'] == elanObjects.success_track.id && tags.indexOf(mapTagName) != -1;
        })[0] || null;
    },
    findMapArea: function (records, areaName) {
      areaName = (globalDataInteractionObj.mapInteractionHandler.currentMap == 'EU') ? areaName : areaName + ' Area'; //Make sure all tags related to area includes 'Area' word at the end in Elan TTN
      //console.log('??????????? findMapArea '+areaName, records);
      if(records && typeof(records.filter) == 'function') {
        return records.filter(function (r) {
            return (r['OBJECT_TYPE_ID'] == elanObjects.scorm.id || r['OBJECT_TYPE_ID'] == elanObjects.success_track.id || r['OBJECT_TYPE_ID'] == elanObjects.curriculum.id) && r['TAGS'].indexOf(areaName) != -1;
          })[0] || null;
      }
    },
    findMapNeighborhood: function (records, neighborhoodTagName) {
      return records.filter(function (r) {
          var tags = $.map(r['TAGS'].split(','), $.trim);
          return r['OBJECT_TYPE_ID'] == elanObjects.scorm.id && tags.indexOf(neighborhoodTagName) != -1;
        })[0] || null;
    },
    findMapNeighborhoodAssessment: function (records, neighborhoodTagName) {
      return records.filter(function (r) {
          var tags = $.map(r['TAGS'].split(','), $.trim);
          return r['OBJECT_TYPE_ID'] == elanObjects.class.id && tags.indexOf(neighborhoodTagName) != -1;
        })[0] || null;
    },
    launchMonthlyFocusCourse: function (learningObjectId, learningObjectParentId) {
      var learningObject = {};
      learningObject.OBJECT_ID = learningObjectId;
      learningObject.OBJECT_PARENT_IDS = learningObjectParentId;
      globalDataInteractionObj.utilities.launchScormCourse(learningObject);
    },
    launchNeighborhoodCourse: function (learningObjectId) {
      var learningObject = {};
      if (typeof(learningObjectId) == 'undefined' || learningObjectId == null) {
        learningObject = globalDataInteractionObj.mapInteractionHandler.currentMapObject;
      } else {
        learningObject = globalDataInteractionObj.utilities.findOneByProperty(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, 'OBJECT_ID', learningObjectId);
      }
      //console.log(learningObject);

      globalDataInteractionObj.utilities.launchScormCourse(learningObject);

      /*
       var handleUserObjectRecord = function(userObjectRecord) {
       //console.log(userObjectRecord);
       if( userObjectRecord ) {
       //console.log('launchNeighborhoodCourse');
       var parameters = {};
       parameters = {
       "type": "launchscorm",
       "object_id": learningObject.OBJECT_ID,
       "record_id": userObjectRecord.RECORD_ID
       };
       $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function(response)  {
       //console.log(response);
       if(response.hasOwnProperty("DATA")) {
       //console.log('launchNeighborhoodCourse');
       var poptions = 'scrollbars=0,directories=0,location=0,menubar=0,toolbar=0,status=0,width=1024,height=768';
       if( coursePopup && !coursePopup.closed ) {
       coursePopup.focus();
       } else {
       coursePopup = window.open(response.DATA.LAUNCH_URL, 'coursePopup', poptions);
       if( !coursePopup ) {
       alert("We launched your course in a new window but if you do not see it, a popup blocker may be preventing it from opening. Please disable popup blockers for this site.");
       } else {
       // When course pop-up is closed return to map page
       $(coursePopup).on('unload', globalDataInteractionObj.utilities.scormUnload);
       }
       }
       }
       });
       } else {
       //console.log(learningObject);
       globalDataInteractionObj.userData.createUserObjectRecord(learningObject, handleUserObjectRecord);
       }
       }

       if( typeof(learningObject.OBJECT_ID) != 'undefined' || learningObject.OBJECT_ID != null ) {
       globalDataInteractionObj.userData.fetchUserObjectRecord(learningObject.OBJECT_ID, handleUserObjectRecord);
       }
       */
    },
    launchScormCourse: function (learningObject) {
      var handleUserObjectRecord = function (userObjectRecord) {
        //console.log(userObjectRecord);
        if (userObjectRecord) {
          //console.log('launchNeighborhoodCourse');
          var parameters = {};
          parameters = {
            "type": "launchscorm",
            "object_id": learningObject.OBJECT_ID,
            "record_id": userObjectRecord.RECORD_ID
          };
          $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function (response) {
            //console.log(response);
            if (response.hasOwnProperty("DATA")) {
              //console.log('launchNeighborhoodCourse');
              var poptions = 'scrollbars=0,directories=0,location=0,menubar=0,toolbar=0,status=0,width=1024,height=768';
              if (coursePopup && !coursePopup.closed) {
                coursePopup.focus();
              } else {
                coursePopup = window.open(response.DATA.LAUNCH_URL, 'coursePopup', poptions);
                //console.log(response.DATA.LAUNCH_URL);
                if (!coursePopup) {
                  alert("We launched your course in a new window but if you do not see it, a popup blocker may be preventing it from opening. Please disable popup blockers for this site.");
                } else {
                  // When course pop-up is closed return to map page
                  $(coursePopup).on('unload', globalDataInteractionObj.utilities.scormUnload);
                }
              }
            }
          });
        } else {
          //console.log(learningObject);
          globalDataInteractionObj.userData.createUserObjectRecord(learningObject, handleUserObjectRecord);
        }
      }

      if (typeof(learningObject.OBJECT_ID) != 'undefined' || learningObject.OBJECT_ID != null) {
        globalDataInteractionObj.userData.fetchUserObjectRecord(learningObject.OBJECT_ID, handleUserObjectRecord);
      }
    },
    scormUnload: function () {
      //console.log('scormUnload');
      // Onunload is called multiple times in the SCORM window - we only want to handle when it is actually closed.
      if (watchPopupClose) {
        clearInterval(watchPopupClose);
      }

      watchPopupClose = setInterval(function () {
        if (coursePopup.closed) {
          //console.log('scormUnload - actually closed');
          clearInterval(watchPopupClose);
          $('a#mapCallToAction').css("pointer-events", "auto");
          globalDataInteractionObj.userData.update();
        }
      }, 500);
    }
  },
  closeMenu: function(){
    $('body').removeClass('no-scroll');
    $('#off-canvas-menu').hide('slow');
    $('#menu-dimmer').hide('slow');
  },
  /*  This is going to be the handler for the ajax data calls across most of the definition.  */
  ajaxUtilities: {
    loading: function (element) {
      //$( element ).html('<p class="text-center loader"><img src="' + site_url + 'assets/pdf-preloader.gif"></p>');
    },

    //ajax switch between API calls and local json
    ajaxSwitch: function (requestType, params, callSource, localCallSource) {
      //globalDataInteractionObj.userData.checkCookie(); <<< it's already done on user init
      localCallSource = localCallSource || null;
      //TODO: Remove from production on launch. Shouldn't need to determine retail & outlet users at the client
      if (localCallSource == 'usercoursedata') {
        localCallSource = globalDataInteractionObj.userData.isRetailOrOutlet() + '/' + localCallSource;
        params = { type: 'getmonthlyfocus' }
      } else if (localCallSource == 'userdata') {
        params = { type: 'userinfowithattr' };
      } else if (localCallSource == 'useritinerary') {
        params = { type: 'getmonthlyfocusitinerary' };
      } else if (localCallSource == 'coursedetaildata') {
        //params = {type: 'coursedetaildata'};
      }
      var propInstance = globalDataInteractionObj.objectProperties,
        objOut = {};
      objOut.type = requestType;
      objOut.dataType = 'json';
      switch (callSource) {
        case 'api':
          objOut.url = propInstance.appRoot;
          objOut.data = {
            'ACT': propInstance.apiRequestParam
          };
          $.each(params, function (key, value) {
            objOut['data'][key] = encodeURIComponent(value);
          });
          if (typeof(objOut['data']['user_id']) == 'undefined') {
            objOut['data']['user_id'] = propInstance.apiUserId;
          }
          break;
        //TODO: refactor as API is workable beyond user data. Localized data should not be a default case
        default:
          objOut.url = 'js/test-data/' + localCallSource + '.json';
      }
      return objOut;
    },
    //this is simply a helper method to concentrate and simplify the known data calls within the definition. Other
    //methods can still use their own ajax requests.
    //This has been added onto a lot so many of the variables do not necessarily make sense in the switch case
    ajaxLauncher: function (typeIn, courseId, parentId, questionId, stepNum) {
      courseId = courseId || null;
      var callParams = {};
      callParams.httpRequestType = 'GET';
      callParams.dataPoints = null;
      callParams.dataSource = 'api';
      callParams.dataResponse = null;
      callParams.detailId = courseId;
      //selector and sequence runner for the async data calls
      switch (typeIn) {
        case 'userdata':
          callParams.sequenceRunner = function (dataIn) {
            globalDataInteractionObj.objectProperties.rawAjaxUserData = dataIn;
            globalDataInteractionObj.userData.setUserLanguage();
            globalDataInteractionObj.courseData.courseInit();
          };
          break;
        case 'usercoursedata':
          callParams.sequenceRunner = function (dataIn) {
            globalDataInteractionObj.objectProperties.rawAjaxCourseData = dataIn;
            globalDataInteractionObj.objectProperties.translations = dataIn['translations'];
            globalDataInteractionObj.userData.setLanguageCommonTerms();
            globalDataInteractionObj.ajaxUtilities.runCourseAjaxSequence();
            globalDataInteractionObj.ajaxUtilities.ajaxLauncher('useritinerary');
            $('#site-nav').show();
            $('.translator-icon').show();
          };
          break;
        case 'useritinerary':
          callParams.sequenceRunner = function (dataIn) {
            globalDataInteractionObj.objectProperties.rawAjaxItineraryData = dataIn;
            globalDataInteractionObj.ajaxUtilities.runUserAjaxSequence();
          };
          break;
        case 'coursedetaildata':
          callParams.sequenceRunner = function (dataIn, courseId) {
            //console.log(courseId);
            globalDataInteractionObj.objectProperties.rawAjaxCourseDetailData = dataIn;
            //globalDataInteractionObj.ajaxUtilities.runCourseDetailAjaxSequence(courseId);
          };
          callParams.dataPoints = { type: 'coursedetaildata', object_id: courseId, children: parentId };
          break;
        case 'coursedetaillink':
          callParams.dataPoints = { type: 'coursedetaillink', object_id: courseId, parent_object_id: parentId };
          break;
        case 'updateuserobjectrecord':
          callParams.dataPoints = {
            type: 'updateuserobjectrecord',
            record_id: courseId,
            status: parentId,
            score: questionId
          };
          break;
        case 'getquizquestions':
          callParams.dataPoints = { type: 'getquizquestions', object_id: courseId, parent_object_id: parentId }
          break;
        case 'submitquizanswers':
          callParams.dataPoints = {
            type: 'submitquizanswers',
            record_id: courseId,
            user_ans_no: parentId,
            question_id: questionId
          };
          break;
        case 'getcoursevideo':
          callParams.dataPoints = {
            type: 'getcoursevideo',
            record_id: courseId,
            step_num: stepNum,
            object_id: questionId,
            parent_object_id: parentId
          };
          break;
        case 'updatecreateuserrecord':
          callParams.dataPoints = {
            type: 'updatecreateuserrecord',
            object_id: courseId,
            parent_object_id: parentId,
            status: questionId
          }
          if (questionId = 'Complete') {
            globalDataInteractionObj.ajaxUtilities.removeDisableClassForNextChild(courseId, parentId);
          }
          break;
        case 'mapsetup':
          globalDataInteractionObj.closeMenu();
          var mlRankMap = arguments[1] || globalDataInteractionObj.userData.mlRankMap();

          ///console.log('*********** mapsetup ***********', mlRankMap);
          //console.log('----------- which map? -----------', arguments);
          if (freshMapObject !== null && !globalDataInteractionObj.isMapLoaded) {
            var mapDiv = jQuery('#vmap');
            mapDiv.empty();
            freshMapObject.reset();
            // console.log("*********************** mapsetup REFRESH ***********************")
          } else if (globalDataInteractionObj.isMapLoaded) {
            // console.log("*********************** mapsetup ALREADY LOADED ***********************", freshMapObject)
          } else {
            // console.log("*********************** mapsetup ***********************", freshMapObject)
          }

          globalDataInteractionObj.userData.setUserMapHandler(function (mlRankMap) {

            if (mlRankMap == 'mlapprentice') {
              globalDataInteractionObj.userData.userMapHandler = mlApprenticeConfig;
            } else if (mlRankMap == 'mlambassador') {
              globalDataInteractionObj.userData.userMapHandler = mlAmbssadorConfig;
            } else if (mlRankMap == 'eu') {
              globalDataInteractionObj.userData.userMapHandler = euConfig;
            }
            // else if (mlRankMap == 'asia') {
            //   globalDataInteractionObj.userData.userMapHandler = mlAsiaConfig;
            // }
            globalDataInteractionObj.userData.setUserMapLanguage(mlRankMap);
            globalDataInteractionObj.mapInteractionHandler.mapInit(mlRankMap);
            globalDataInteractionObj.interactionHandler.subInit(mlRankMap);
            globalDataInteractionObj.mapInteractionHandler.resetMapThen(mlRankMap);
            //console.log('---- userData ------', globalDataInteractionObj.userData.userMapHandler);
          }, mlRankMap);
          break;

        case 'profilesetup':
          var mlRankMap = globalDataInteractionObj.userData.mlRankMap() || 'mlapprentice';

          globalDataInteractionObj.userData.setUserMapHandler(function (mlRankMap) {
            //var userCourseResponse = globalDataInteractionObj.objectProperties.rawAjaxCourseData;
            //var courses_completed = userCourseResponse.DATA.CUSTOM_ATTRIBUTES.COURSES_COMPLETED;

            if(!globalDataInteractionObj.userData.userMapHandler) {
              if (mlRankMap == 'mlapprentice') {
                globalDataInteractionObj.userData.userMapHandler = mlApprenticeConfig;
              } else if (mlRankMap == 'mlambassador') {
                globalDataInteractionObj.userData.userMapHandler = mlAmbssadorConfig;
              } else if (mlRankMap == 'eu') {
                globalDataInteractionObj.userData.userMapHandler = euConfig;
              }
              // else if (mlRankMap == 'asia') {
              //   globalDataInteractionObj.userData.userMapHandler = mlAsiaConfig;
              // }
              globalDataInteractionObj.userData.setUserMapLanguage(mlRankMap);
              globalDataInteractionObj.mapInteractionHandler.mapInit(mlRankMap);
              globalDataInteractionObj.interactionHandler.subInit(mlRankMap);
              globalDataInteractionObj.mapInteractionHandler.wireMapButtons();
            }
            //globalDataInteractionObj.userData.displayMeterCompletions(courses_completed);
            globalDataInteractionObj.userData.updateProfile();

          }, mlRankMap);

          break;
      }
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch(callParams.httpRequestType, callParams.dataPoints, callParams.dataSource, typeIn))
        .done(function (data) {
        callParams.dataResponse = data;
        if (typeIn == 'coursedetaildata') {
          globalDataInteractionObj.ajaxUtilities.fillCourseDetailModal(data, courseId);
        } else if (typeIn == 'coursedetaillink') {
          globalDataInteractionObj.ajaxUtilities.runCourseDetailLinkAjaxSequence(data, courseId);
        } else if (typeIn == 'getquizquestions' || typeIn == 'getquizanswers') {
          globalDataInteractionObj.handlebarsUtils.setTemplates('quiz-modal', data);
          quiz.data = data;
          //console.log(quiz.data);
          $('div.pre-load-modal').hide();
        } else if (typeIn == 'getcoursevideo') {
          var videoModal = $('#video-modal');

          var video_url = '';
          var video_bitrate = 0;
          data.DATA.MEDIA_INFO.encodings.forEach(function (encoding) {
            if (encoding.container_type == 'Mpegts' && encoding.video_bitrate > video_bitrate) {
              video_url = encoding.master_playlist_url;
              video_bitrate = encoding.video_bitrate;
            }
          });

          video_url = video_url.replace(/^http:\/\//i, 'https://');

          $('#video-modal div.inner').html('<i class="icon-close-btn"></i><video id="video-coach" width="960" height="540" preload="auto" class="video-js vjs-default-skin" controls><source src="" type="application/x-mpegURL" id="video-source"></source></video>');
          $('#video-source').attr('src', video_url);

          var player = videojs('#video-coach', { "autoplay": false });
          $('div.pre-load-modal').fadeOut(300);
          //player = videoModal.find('video')[0];
          //toggleMenu('close', .01);
          TweenLite.to(videoModal, .35, { autoAlpha: 1, ease: 'Power3.easeIn' });

          monthly_focus.child = true;
          player.load();
          //player.ready(function() {
          // player.play();
          //});
          globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updatecreateuserrecord', questionId, parentId, 'Complete');
          $("#" + questionId).parents("li").next().children().children().removeClass('disabled');


          player.on('ended', function () {
            //globalDataInteractionObj.ajaxUtilities.updateObjectRecord(courseId, 'Complete', questionId);

            TweenLite.to(videoModal, .35, { autoAlpha: 0, ease: 'Power3.easeOut' });
            player.dispose();
            monthly_focus.child = false;
          });
          //body.addClass('no-scroll');

          $(document).on('click touchstart', '#video-modal', function (e) {
            var clicked = $(e.target);
            // //console.log(clicked);
            if (clicked.is('#video-modal') || clicked.is('.icon-close-btn')) {
              if ($('#video-coach').is("div")) {
                // Destroys the video player and does any necessary cleanup
                var player = videojs("#video-coach");
                if (player) {
                  player.dispose();
                  //$("div#video-modal #video-coach").empty();
                }
              }
              monthly_focus.child = false;
              TweenLite.to(videoModal, .35, { autoAlpha: 0, ease: 'Power3.easeOut' });
              return false;
            }
          });
        } else if (typeIn == 'userdata') {
          globalDataInteractionObj.objectProperties.userObjectRecords = data.OBJECT_RECORDS;
        }

        // //console.log(data);
      }).fail(function (jqxhr, status) {
        //console.log(jqxhr + ' status: ' + status);
        callParams.dataResponse = { 'data': 'none' };
      }).always(function () {
        if (typeof(callParams.sequenceRunner) == 'function') {
          callParams.sequenceRunner(callParams.dataResponse, callParams.detailId);
        }
      });
    },
    /*
     *  These are the various initialization methods for the user, course, and course detail, that fetch the various
     *  API responses and render them into their respective views.
     */
    runUserAjaxSequence: function () {
      var userDataObj = globalDataInteractionObj.userData,
        userItineraryResponse = globalDataInteractionObj.objectProperties.rawAjaxItineraryData,
        userCourseResponse = globalDataInteractionObj.objectProperties.rawAjaxCourseData;
      if(userCourseResponse.DATA && userCourseResponse.DATA != 'undefined') {
        var completionPercentage = globalDataInteractionObj.userData.calculateCourseCompletionPercentage(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.COURSES_COMPLETED, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.TOTAL_COURSES),
          completionAverage = globalDataInteractionObj.userData.calculateCourseCompletionAverage(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.COURSES_COMPLETED, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.TOTAL_SCORES),
          averageAssessmentPercentage = globalDataInteractionObj.userData.displayAssessmentAverage(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.ASSESSMENT_AVE_PERCENTAGE),
          completionAssessmentPercentage = globalDataInteractionObj.userData.calculateAssessmentCompletion(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.TOTAL_ASSESSMENTS, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.ASSESSMENTS_COMPLETED);
        userDataObj.displayNameAndTitle(userCourseResponse.DATA.FIRST_NAME, userCourseResponse.DATA.LAST_NAME, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.ML_RANK, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.ML_RANK_SINCE);
        userDataObj.displayCityStateCountry(userCourseResponse.DATA.LocationCity, userCourseResponse.DATA.LocationState, userCourseResponse.DATA.LocationCountry);
        userDataObj.displayAssociateRole(userCourseResponse.DATA.Role);
        userDataObj.fetchMonthlyFocusScores(userItineraryResponse);
        userDataObj.displayJoinCoach(userCourseResponse.DATA['Last Hire Date']);
        userDataObj.displayAssociateDate(userCourseResponse.DATA.DateInJob);
        userDataObj.displayAssociateAvatar(userCourseResponse.DATA.USER_AVATAR) || 'images/profile-empty-avatar.png';
        userDataObj.displaySliderAvatar(userCourseResponse.DATA.USER_AVATAR);
        userDataObj.displayCourseCompletionPercentage(completionPercentage);
        userDataObj.calculateAndDisplayRadialProgressBar(completionPercentage, 'progress-monthly-completion-circle');
        userDataObj.displayCourseCompletionAverage(completionAverage, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.COURSES_COMPLETED, userCourseResponse.DATA.CUSTOM_ATTRIBUTES.TOTAL_SCORES);
        userDataObj.displayMeterCompletions(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.COURSES_COMPLETED);
        userDataObj.displayAssessmentCompletions(completionAssessmentPercentage);
        userDataObj.calculateAndDisplayRadialProgressBar(completionAverage, 'progress-monthly-average-circle');
        userDataObj.calculateAndDisplayRadialProgressBar(completionPercentage, 'progress-profile-monthly-completion-circle');
        userDataObj.calculateAndDisplayRadialProgressBar(completionAssessmentPercentage, 'progress-assessment-completion-circle');
        userDataObj.calculateAndDisplayRadialProgressBar(completionAssessmentPercentage, 'progress-profile-assessment-completion-circle');
        userDataObj.calculateAndDisplayRadialProgressBar(userCourseResponse.DATA.CUSTOM_ATTRIBUTES.ASSESSMENT_AVE_PERCENTAGE, 'progress-assessment-average-circle');
      }
    },
    runCourseAjaxSequence: function () {
      var userCourseDataResponse = {
        ' Row 1': globalDataInteractionObj.objectProperties.rawAjaxCourseData[' Row 1'],
        ' Row 2': globalDataInteractionObj.objectProperties.rawAjaxCourseData[' Row 2'],
        ' Row 3': globalDataInteractionObj.objectProperties.rawAjaxCourseData[' Row 3'],
        ' Row 4': globalDataInteractionObj.objectProperties.rawAjaxCourseData[' Row 4']
      };
      // //console.log(userCourseDataResponse);

      globalDataInteractionObj.handlebarsUtils.setTemplates('course-data-summary-template', userCourseDataResponse);
      globalDataInteractionObj.courseData.carouselSequence();
      globalDataInteractionObj.courseDetail.eventInit();
    },
    runCourseDetailAjaxSequence: function (courseElem) {
      var courseDetailData = globalDataInteractionObj.objectProperties.rawAjaxCourseDetailData;
      var mappedData = globalDataInteractionObj.courseDetail.getDetailContentById(courseDetailData.courseDetail, courseElem);
      //console.log(mappedData);
      globalDataInteractionObj.handlebarsUtils.setTemplates('course-detail-overlay', mappedData);
      globalDataInteractionObj.courseDetail.getMatchingCourseAttributes(courseElem);
      globalDataInteractionObj.courseDetail.setUpOverlay('.course-detail');

      $('.close-overlay').on('click touchstart', function () {
        $('.course-detail').hide();
        globalDataInteractionObj.courseDetail.prepOverlayTeardown('.course-detail');
      });
    },
    updateObjectRecord: function (recordId, status, currentLink) {
      //Update the user object record.
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updateuserobjectrecord', recordId, status);
      // remove the disabled class to the next sibling
      //console.log(currentLink);
      $("#" + currentLink).parents("li").next().children().children().removeClass('disabled');
    },
    removeDisableClassForNextChild: function (elementId, parentId) {
      $('#' + elementId).parents('li').find('img').attr('src', '/images/checkboxes/check-mark-green.png');
      if ($('#' + elementId).parents('li').next().length > 0 && !$('#' + elementId).hasClass('off-canvas-video')) {
        $('#' + elementId).parents("li").next().children().children().removeClass('disabled');
        globalDataInteractionObj.objectProperties.rawAjaxCourseData[monthly_focus['location'][0]][monthly_focus['location'][1]].children[parseInt($('#' + elementId).attr('attr-location')) + 1].disabled = '';
        //globalDataInteractionObj.objectProperties.rawAjaxCourseData[' Row 1'][1].children[2].disabled = '';
      } else {
        $('#' + parentId).addClass('open');
        $('#' + parentId).parent('div').prev().prev().prev().children('img').addClass('open');
      }
      loop1:
        for (var row_key in globalDataInteractionObj.objectProperties.rawAjaxCourseData) {
          // //console.log(globalDataInteractionObj.objectProperties.rawAjaxCourseData[key]);
          for (var object_key in globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key]) {
            for (var child_key in globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['children']) {
              if (globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['children'][child_key]['object_id'] == elementId) {
                globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['children'][child_key]['is_objective_complete'] = true;
                break loop1;
              }
            }
          }
        }

    },
    runCourseDetailLinkAjaxSequence: function (data) {

    },
    fillCourseDetailModal: function (data, courseElem) {
      data.courseSummary = $('.course-description-' + courseElem).text();
      globalDataInteractionObj.handlebarsUtils.setTemplates('course-detail-overlay', data);
      $('div.pre-load-modal').hide();
      globalDataInteractionObj.courseDetail.getMatchingCourseAttributes(courseElem);
      globalDataInteractionObj.courseDetail.setUpOverlay('.course-detail');

      if (!monthly_focus.child_has_been_opened) {
        $('.course-detail').on('click touchstart', function (e) {

          var clicked = $(e.target);
          //console.log(clicked);
          if ((clicked.is('.icon-close-btn') || clicked.is('.course-detail')) && !monthly_focus.child) {
            globalDataInteractionObj.courseDetail.prepOverlayTeardown('.course-detail');
          }
          // //console.log('close class-link is visible');

          // //console.log('Close class-link');
          //return false;
        });
        monthly_focus.child_has_been_opened = true;
      }
      window.onscroll = function () {
        if (monthly_focus.class_link) {
          if (!monthly_focus.child) {
            $('.course-detail').hide();
            globalDataInteractionObj.courseDetail.prepOverlayTeardown('.course-detail');
          }
        }
      };
      //$('body').on('click', '.objective-link', function()  { globalDataInteractionObj.ajaxUtilities.ajaxLauncher('coursedetaillink', this.id, this.getAttribute('attr-parent')); });

    },
    fillQuizQuestions: function (objectId, parentId) {
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher('getquizquestions', objectId, parentId);
      // globalDataInteractionObj.courseDetail.setUpOverlay('.quiz-container');
    },
    //TODO: Pull this function out of a production deployment
    addAjaxPathIndex: function (templateName) {
      var fullPath;
      (globalDataInteractionObj.objectProperties.forcePath) ? fullPath = site_url + '/index.php/' + templateName : fullPath = site_url + templateName;
      return fullPath;
    }
    /*  End initialization methods  */
  },
  /*  End ajax utilities  */
  //container for the handlebars helper and initialization methods
  handlebarsUtils: {
    setHandlebarsHelpers: function () {
      Handlebars.registerHelper('greaterThanThree', function (lengthIn, options) {
        if (lengthIn > 3) {
          return options.fn(this);
        }
      });
      Handlebars.registerHelper('isOpen', function (param, options) {
        if (param == 'false') {
          return options.fn(this);
        }
      });
      Handlebars.registerHelper('isComplete', function (param, options) {
        if (param == 'true') {
          return options.fn(this);
        }
      });
      //this (an other helpers) will most likely need to be refined as the Elan stuff is ready
      Handlebars.registerHelper('isLink', function (param, options) {
        if (param == 'link' || param == 'true') {
          return options.fn(this);
        } else {
          return options.inverse(this);
        }
      });
      Handlebars.registerHelper('isInstagram', function (param, options) {
        if (param == 'instagram' || param == 'true') {
          return options.fn(this);
        } else {
          return options.inverse(this);
        }
      });
      Handlebars.registerHelper('stringTitle', function (passedString) {
        var theString = passedString.replace(/\(/g, '<br/> (');
        return new Handlebars.SafeString(theString)
      });
    },
    //defining a single method for the initialization of the handlebars templates
    setTemplates: function (elemIdIn, jsonResponse) {
      this.setHandlebarsHelpers();
      var jQselector = "#" + elemIdIn;
      var classMap = globalDataInteractionObj.objectProperties.handlebarsDomMap;
      for (var prop in classMap) {
        var jqTemplateElem = '#' + prop,
          jqViewElem = '.' + classMap[prop];
        if (jQselector == jqTemplateElem) {
          var sourceScript = $(jqTemplateElem).html(),
            template = Handlebars.compile(sourceScript),
            compiledTemplate = template(jsonResponse);
          $(jqViewElem).html(compiledTemplate);
        }
        ;
      }
    }
  },
  //container for the user data interactions
  userData: {
    type: 'userdata',
    userLearningObjects: {},
    userMapHandler: null,
    //TODO: This function should not be necessary for a production launch
    isRetailOrOutlet: function () {
      var pathToUserCourseFile;
      switch (globalDataInteractionObj.objectProperties.apiUserId) {
        //case '1':
        case '4':
          pathToUserCourseFile = 'outlet';
          break;
        default:
          pathToUserCourseFile = 'retail';
      }
      return pathToUserCourseFile;
    },
    checkCookie: function () {
      $(document).on('click touchstart', function(e){
        var currentUserId = globalDataInteractionObj.utilities.getCookie("exp_elan_user_id");
        var currentUserName = globalDataInteractionObj.utilities.getCookie("exp_elan_username");

        //console.log("checkCookie "+currentUserId, currentUserName);

        if (currentUserId == '') {
          var logoutPath = $("a.logout-link").attr('href');
          location.href = logoutPath;
        }
      });
    },
    getJourneyVideo: function () {
      var languageID = globalDataInteractionObj.objectProperties.apiUserLanguage.id;

      switch (languageID) {
        case 1:
          $('.off-canvas-video').attr("id", "143");
          break;
        case 6:
          $('.off-canvas-video').attr("id", "1626");
          break;
        case 11:
          $('.off-canvas-video').attr("id", "1627");
          break;
        case 12:
          $('.off-canvas-video').attr("id", "1628");
          break;
        case 13:
          $('.off-canvas-video').attr("id", "1629");
      }
      //console.log(languageID);
    },
    displayNameAndTitle: function (firstNameIn, lastNameIn, levelIn, rankSinceIn) {
      $('.assoc-name').text(firstNameIn + ' ' + lastNameIn);
      $('.assoc-rank-date').text(rankSinceIn);
      if (levelIn != 'ML APPRENTICE') {
        $('.assoc-rank').text(globalDataInteractionObj.utilities.lang('mltrainee'));
        $('.assoc-rank-footer').text(globalDataInteractionObj.utilities.lang('mltraineefooter'));
        $('.assoc-rank-next').text(globalDataInteractionObj.utilities.lang('mlapprentice'));
      }
      else {
        $('.assoc-rank').text(levelIn);
      }

    },
    displayCityStateCountry: function (cityIn, stateIn, countryIn) {
      //console.log(cityIn);
      var theComma = ", ";
      if (cityIn == null) {
        cityIn = '';
        theComma = '';
      }
      if (stateIn == null) {
        stateIn = '';
        theComma = '';
      }
      if (countryIn == null) {
        countryIn = '';
      }
      $('.assoc-city').text(cityIn + '' + theComma + '' + stateIn);
      $('.assoc-country').text(countryIn);
    },
    displayJoinCoach: function (joinDateIn) {
      $('.joined-coach-date').text(joinDateIn);
    },
    displayAssociateRole: function (associateRoleIn) {
      $('.associate-role').text(associateRoleIn);
    },
    displayAssociateDate: function (associateDateIn) {
      $('.associate-date').text(associateDateIn);
    },
    displayAssociateAvatar: function (associateAvatarIn) {
      /*      $('.profile-main-image-avatar').css({
       "background-image": "url(" + associateAvatarIn + ")" //,
       // "background-repeat": "no-repeat",
       // "background-size": "cover",
       // "background-position": "center"
       });*/
      if (associateAvatarIn != '') {
        $('.profile-main-image-avatar').css({
          "background-image": "url(" + associateAvatarIn + ")" //,
          // "background-repeat": "no-repeat",
          // "background-size": "cover",
          // "background-position": "center"
        });
      }
    },
    displaySliderAvatar: function (associateAvatarIn) {
      // $('.nav-avatar').css({
      //   "background-image": "url(" + associateAvatarIn + ")" //,
      //   // "background-repeat": "no-repeat",
      //   // "background-size": "cover",
      //   // "background-position": "center"
      // });
      if (associateAvatarIn != '') {
        $('.nav-avatar').css({
          "background-image": "url(" + associateAvatarIn + ")" //,
          // "background-repeat": "no-repeat",
          // "background-size": "cover",
          // "background-position": "center"
        });
      }
      // else {
      //   $('.nav-avatar').css({
      //     "background-image": "url(" + globalDataInteractionObj.objectProperties.appRoot + "images/profile-empty-avatar.jpg)",
      //     "background-repeat": "no-repeat",
      //     "background-size": "cover",
      //     "background-position": "center"
      //   });
      // }
    },
    displayNextDestination: function () {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
      if(currentNeighborhood) {
        var neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', currentNeighborhood, 'NEIGHBORHOOD');
        //console.log("~*~*~*~*~*~*~*~*~*~*~*~*~*~ displayNextDestination currentNeighborhood ~*~*~*~*~*~*~*~*~*~*~*~*~*~", currentNeighborhood);
        //console.log("displayNextDestination image info from cat_url_title", neighborhoodInfo.cat_url_title);
        $('.next-destination').text(globalDataInteractionObj.utilities.lang(neighborhoodInfo.cat_url_title));
        $('.myprofile-row-3-2').html('<img id="destination-image" src="' + globalDataInteractionObj.objectProperties.appRoot + 'images/destinations/' + globalDataInteractionObj.objectProperties.apiUserLanguage.abbreviation + '/' + neighborhoodInfo.cat_url_title + '.jpg" />');
      }
    },
    displayTargetArrival: function () {
      var monthNames = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"];
      var msec = Date.parse("May 1, 2017");
      var centerTargetDate = new Date(msec);
      centerTargetDate = new Date(centerTargetDate.setMonth(centerTargetDate.getMonth()));
      var leftTargetDate = new Date(centerTargetDate.setMonth(centerTargetDate.getMonth() - 1));
      var rightTargetDate = new Date(centerTargetDate.setMonth(centerTargetDate.getMonth() + 2));
      var centerTargetYear = centerTargetDate.getUTCFullYear();
      var centerTargetMonth = monthNames[centerTargetDate.getUTCMonth() - 1];
      var centerTargetDay = centerTargetDate.getUTCDate();
      var leftTargetYear = leftTargetDate.getUTCFullYear();
      var rightTargetYear = rightTargetDate.getUTCFullYear();
      var leftTargetMonth = monthNames[leftTargetDate.getUTCMonth()];
      var rightTargetMonth = monthNames[rightTargetDate.getUTCMonth()];
      $('.left-target-arrival').text(globalDataInteractionObj.utilities.lang(leftTargetMonth).substring(2, 12) + ' ' + leftTargetYear);
      $('.center-target-arrival').text(globalDataInteractionObj.utilities.lang(centerTargetMonth) + ' ' + centerTargetYear);
      $('.right-target-arrival').text(globalDataInteractionObj.utilities.lang(rightTargetMonth).substring(0, 8) + ' ' + rightTargetYear);
    },
    //calculate the two digit integer to be used for the completion percentage
    calculateCourseCompletionPercentage: function (completedCourses, totalCourses) {
      var intOut = Math.round((completedCourses / totalCourses).toFixed(2) * 100);
      return intOut;
    },
    //display the completion percentage into the views
    displayCourseCompletionPercentage: function (intIn) {
      if (isNaN(intIn)) {
        var percentageString = '0&#37;';
      } else {
        var percentageString = intIn + '&#37;';
      }
      $('.data-course-completion').html(percentageString);

      if (intIn >= 85) {
        $('.data-course-completion-color').attr("stroke", "rgba(100,196,151,1.0)");
        $('.progress-profile-monthly-completion-circle').attr("stroke", "rgba(100,196,151,1.0)");
        $('.progress-monthly-completion-circle').attr("stroke", "rgba(100,196,151,1.0)");
      } else {
        $('.data-course-completion-color').attr("stroke", "rgba(247,79,61,1.0)");
        $('.progress-profile-monthly-completion-circle').attr("stroke", "rgba(247,79,61,1.0)");
        $('.progress-monthly-completion-circle').attr("stroke", "rgba(247,79,61,1.0)")
      }
    },
    //calculate the two digit integer to be used for the average percentage
    calculateCourseCompletionAverage: function (completedCourses, totalScores) {
      var intOut = Math.round((totalScores / completedCourses));
      return intOut;
    },

    fetchMonthlyFocusScores: function (dataIn) {
      var monthlyFocusBody = $("div#accordion div#monthlyfocus div.panel-body");
      $(monthlyFocusBody).empty();
      if (dataIn.hasOwnProperty('DATA')) {

        $.each(dataIn.DATA, function (mindex, mvalue) {
          if (mvalue.TOTAL_ASSESSMENTS != 0) {
            var completion = Math.round((mvalue.ASSESSMENTS_COMPLETED / mvalue.TOTAL_ASSESSMENTS).toFixed(2) * 100);
            var monthlyfocus = '<div class="col-md-12 panel-row">';
            monthlyfocus += '<div class="col-md-1"></div>';
            monthlyfocus += '<div class="col-md-4 monthlyfocus-itinerary-course-title vertical-align"><h3>' + globalDataInteractionObj.utilities.lang(mindex) + '</h3></div>';
            monthlyfocus += '<div class="col-md-2 monthly-focus-scores">';
            if (completion >= 85) {
              monthlyfocus += '<div class="monthly-panel-circle monthly-focus-comp-score" style="background-color:rgb(100,196,151);">';
            } else {
              monthlyfocus += '<div class="monthly-panel-circle monthly-focus-comp-score" style="background-color:rgb(247,79,61);">';
            }
            monthlyfocus += '<h4>' + completion + '&#37;</h4>'
            monthlyfocus += '</div>';
            monthlyfocus += '<h5 class="monthly-score-text">' + globalDataInteractionObj.utilities.lang('completion') + '</h5>';
            monthlyfocus += '</div>';
            monthlyfocus += '<div class="col-md-2 monthly-focus-scores">';
            if (mvalue.ASSESSMENT_AVG_PERCENTAGE >= 85) {
              monthlyfocus += '<div class="monthly-panel-circle monthly-focus-avg-score" style="background-color:rgb(100,196,151);">';
            } else {
              monthlyfocus += '<div class="monthly-panel-circle monthly-focus-avg-score" style="background-color:rgb(247,79,61);">';
            }
            monthlyfocus += '<h4>' + mvalue.ASSESSMENT_AVG_PERCENTAGE + '&#37;</h4>';
            monthlyfocus += '</div>';
            monthlyfocus += '<h5 class="monthly-score-text">' + globalDataInteractionObj.utilities.lang('score') + '</h5>';
            monthlyfocus += '</div>';
            monthlyfocus += '</div>';
            $(monthlyFocusBody).append(monthlyfocus);
            //console.log(mindex);
            //console.log(mvalue);
          }
        });
      }
    },

    //display the Monthly Average Score into the views
    displayCourseCompletionAverage: function (intIn, courseCompletedIn, totalScoresIn) {
      if (isNaN(intIn)) {
        var scoreString = '0&#37;';
      } else {
        var scoreString = intIn + '&#37;';
      }
      var numberCourses = courseCompletedIn;
      var numberScores = totalScoresIn / courseCompletedIn;
      var totalPercent = 100 - numberScores;


      $('.data-course-average-score').html(scoreString);
      $('.data-course-completed').css({ 'width': numberScores + '%' });
      $('.data-course-scores').css({ 'width': totalPercent + '%' });


      if (intIn >= 85) {
        $('.data-course-average-color').attr("stroke", "rgba(100,196,151,1.0)");
        $('.progress-monthly-average-circle').attr("stroke", "rgba(100,196,151,1.0)");
      } else {
        $('.data-course-average-color').attr("stroke", "rgba(247,79,61,1.0)");
        $('.progress-monthly-average-circle').attr("stroke", "rgba(247,79,61,1.0)");
      }

    },
    calculateAssessmentCompletion: function (totalAssessmentIn, assessmentsPassedIn) {
      var intOut = Math.round((assessmentsPassedIn / totalAssessmentIn).toFixed(2) * 100);
      return intOut;
    },
    displayAssessmentCompletions: function (intOut) {
      //console.log(intOut);
      if (isNaN(intOut)) {
        $('.data-assessments-complete').html('0&#37;');
      } else {
        $('.data-assessments-complete').html(intOut + '&#37;');
      }

      if (intOut >= 85) {
        $('.data-assessment-completion-color').attr("stroke", "rgba(100,196,151,1.0)");
        $('.data-assessment-completion-color').attr("fill", "rgba(100,196,151,1.0)");
        $('.progress-assessment-completion-circle').attr("stroke", "rgba(100,196,151,1.0)");
        $('.progress-profile-assessment-completion-circle').attr("stroke", "rgba(100,196,151,1.0)");
      } else {
        $('.data-assessment-completion-color').attr("stroke", "rgba(247,79,61,1.0)");
        $('.data-assessment-completion-color').attr("fill", "rgba(247,79,61,1.0)");
        $('.progress-assessment-completion-circle').attr("stroke", "rgba(100,196,151,1.0)");
        $('.progress-profile-assessment-completion-circle').attr("stroke", "rgba(247,79,61,1.0)");
      }
    },
    displayAssessmentAverage: function (assessmentPercentage) {
      //console.log(assessmentPercentage);
      $('.data-assessments-percent').html(assessmentPercentage + '&#37;');

      if (assessmentPercentage >= 85) {
        $('.data-assessment-average-color').attr("stroke", "rgba(100,196,151,1.0)");
        $('.data-monthly-average-color').attr("fill", "rgba(100,196,151,1.0)");
        $('.progress-assessment-average-circle').attr("stroke", "rgba(100,196,151,1.0)");
      } else {
        $('.data-assessment-average-color').attr("stroke", "rgba(247,79,61,1.0)");
        $('.data-monthly-average-color').attr("fill", "rgba(247,79,61,1.0)");
        $('.progress-assessment-average-circle').attr("stroke", "rgba(247,79,61,1.0)");
      }
    },
    displayMeterCompletions: function (courseCompletedIn) {
      //console.log('courseCompletedIn', courseCompletedIn);
      var completedCourses = courseCompletedIn,
        gauge = $('#g2.gauge');
      if(gauge != 'undefined' && completedCourses) { //gauge.attr('data-value')
        gauge.attr('data-value', parseInt(completedCourses));
        g2.refresh(courseCompletedIn);
      }
    },

    //
    calculateAndDisplayRadialProgressBar: function (intIn, elemIn) {
      var jqSelector = "." + elemIn,
        circleObjInstance = globalDataInteractionObj.utilities.setProgressionCircleParameters($(jqSelector));
      $(jqSelector).attr('stroke-dasharray', circleObjInstance.elemCircumference);
      //TODO: something to validate that intIn is always be between 0 and 100 (exception handling, unit test outside the global definition)
      var radialProgressInt = Math.round(intIn * circleObjInstance.elemRatio);
      //the animation effect needs to happen after the course completion percentage has been received. Setting an arbitrary time for now.
      setTimeout(function () {
        $(jqSelector).addClass('animation-trigger');
        $(jqSelector).attr('stroke-dashoffset', radialProgressInt);
      }, 1000);
    },
    //
    displayPassportStamps: function () {
      var areaObjectId = '';
      var areaObjectStatus = '';
      var numberOfStamps = 0;
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var areas = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', '0');
      areas = globalDataInteractionObj.utilities.sortByProperty(areas, 'unlockorder');

      $.each(areas, function (akey, avalue) {
        //console.log(avalue.cat_url_title);
        var areaObject = globalDataInteractionObj.utilities.findMapArea(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, avalue.cat_name);
        //console.log(areaObject);
        areaObjectStatus = '';
        if (typeof(areaObject) == 'object' && areaObject != null) {
          areaObjectId = areaObject.OBJECT_ID;
          areaObjectStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(areaObjectId);
          if (areaObjectStatus == 'Complete') {
            numberOfStamps++;
          }
        }
      });

      $(".stamps-amount").text(numberOfStamps);
    },
    //
    displayJourneyAccordion: function () {
      var accordionBody = $("div#accordion div#mlapprentice div#accordion2");
      $(accordionBody).empty();

      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var areas = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', '0');
      areas = globalDataInteractionObj.utilities.sortByProperty(areas, 'unlockorder');

      var panelNumber = 0;
      $.each(areas, function (akey, avalue) {
        var areaObject = globalDataInteractionObj.utilities.findMapArea(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, avalue.cat_name);
        //console.log("----------each areas", avalue.cat_name);
        if (typeof(areaObject) == 'object' && areaObject != null) {
          panelNumber += 1;
          var currentAreaStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(areaObject.OBJECT_ID);
          var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', avalue.cat_id);
          neighborhoods = globalDataInteractionObj.utilities.sortByProperty(neighborhoods, 'unlockorder');
          //console.log("--------------------neighborhoods", neighborhoods);
          if (neighborhoods && neighborhoods.length > 0) {
            var currentAreaScore = 0;
            var neighborhoodRow = '';
            neighborhoodRow += '<div class="panel area-row panel-default panel-' + panelNumber + ' col-lg-12 col-md-12">';
            neighborhoodRow += '<button class="btn-toggle-sub" type="button" data-toggle="collapse" data-target="#target' + panelNumber + '" data-parent="#accordion2"><span class="is-open-sub glyphicon glyphicon-plus plusMinus"></span>';
            neighborhoodRow += '<div class="col-md-5 area-panel vertical-center"><h3 class="area-title">' + globalDataInteractionObj.utilities.toProperCase(globalDataInteractionObj.utilities.truncateString(areaObject.OBJECT_NAME)) + '</h3><h3 class="area-name">' +
              globalDataInteractionObj.utilities.lang(avalue.cat_url_title) + '</h3></div>';
            if (currentAreaStatus == 'Complete') {
              neighborhoodRow += '<div class="col-md-3 area-score vertical-center">';
              neighborhoodRow += '<div class="area-avg-score-' + areaObject.OBJECT_ID + ' panel-circle">';
              neighborhoodRow += '<span class="current-area-score-' + areaObject.OBJECT_ID + '"></span>';
              neighborhoodRow += '</div>';
              neighborhoodRow += '<h5 class="score-text-area">' + globalDataInteractionObj.utilities.lang('score') + '</h5>';
              neighborhoodRow += '</div>';
              neighborhoodRow += '<div class="col-md-2 itinerary-links area-links-complete vertical-center"><a class="callToAction" data-objectid="' + areaObject.OBJECT_ID + '" href="javascript://">' + globalDataInteractionObj.utilities.lang('review') + '</a></div>';
              //console.log(currentNeighborhoodScore);
            } else if (currentAreaStatus == 'Started') {
              neighborhoodRow += '<div class="col-md-3 area-score-progress vertical-center">' + globalDataInteractionObj.utilities.lang('inprogress') + '</div>';
              neighborhoodRow += '<div class="col-md-2 itinerary-links area-links-inprogress vertical-center"><a class="callToAction" data-objectid="' + areaObject.OBJECT_ID + '" href="javascript://">' + globalDataInteractionObj.utilities.lang('continue') + '</a></div>';
            }
            neighborhoodRow += '</button><div id="target' + panelNumber + '" class="panel-collapse collapse">';
            neighborhoodRow += '<div class="panel-body">';
            $.each(neighborhoods, function (nkey, nvalue) {
              var neighborhoodObject = globalDataInteractionObj.utilities.findMapNeighborhood(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, nvalue.cat_name);
              //console.log(neighborhoodObject);
              if (typeof(neighborhoodObject) == 'object' && neighborhoodObject != null) {
                var currentNeighborhoodStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(neighborhoodObject.OBJECT_ID);
                var currentNeighborhoodScore = globalDataInteractionObj.userData.getUserObjectRecordScore(neighborhoodObject.OBJECT_ID);
				//console.log(currentNeighborhoodScore);
                if (currentNeighborhoodStatus == 'Started') {
                  neighborhoodRow += '<div class="panel-body"><div class="col-lg-12 col-md-12 panel-row in-progress">';
                } else {
                  neighborhoodRow += '<div class="panel-body"><div class="col-lg-12 col-md-12 panel-row">';
                }

                neighborhoodRow += '<span class="itinerary-completed-check">';
                if (currentNeighborhoodStatus == 'Complete') {
                  neighborhoodRow += '<img src="' + globalDataInteractionObj.objectProperties.appRoot + 'images/checkboxes/check-mark-green.png" class="image-checkbox-itinerary"/>';
                } else {
                  neighborhoodRow += '<img src="' + globalDataInteractionObj.objectProperties.appRoot + 'images/checkboxes/nocheck-mark.png" class="image-checkbox-itinerary"/>';
                }
                neighborhoodRow += '</span>';
                if (currentNeighborhoodScore >= 85) {
                  neighborhoodRow += '<div class="col-md-5 itinerary-course-title" style="color: #64C497 !important"><h3>' + neighborhoodObject.OBJECT_NAME + '</h3><h3 class="neighborhood-name">' + globalDataInteractionObj.utilities.lang(nvalue.cat_url_title) + '</h3></div>';
                }
                else {
                  neighborhoodRow += '<div class="col-md-5 itinerary-course-title"><h3>' + neighborhoodObject.OBJECT_NAME + '</h3><h3 class="neighborhood-name">' + globalDataInteractionObj.utilities.lang(nvalue.cat_url_title) + '</h3></div>';
                }
                if (currentNeighborhoodStatus == 'Complete') {
                  neighborhoodRow += '<div class="col-md-3 itinerary-score">';
                  if (currentNeighborhoodScore >= 85) {
                    neighborhoodRow += '<div class="panel-circle itinerary-score" style="background-color:rgb(100,196,151);">';
                  }
                  else {
                    neighborhoodRow += '<div class="panel-circle itinerary-score" style="background-color:rgb(247,79,61);">';
                  }
                  neighborhoodRow += '<span class="single-data-course-average-score">' + currentNeighborhoodScore + '%</span>';
                  neighborhoodRow += '</div>';
                  neighborhoodRow += '<h5 class="score-text">' + globalDataInteractionObj.utilities.lang('score') + '</h5>';
                  neighborhoodRow += '</div>';
                  neighborhoodRow += '<div class="col-md-2 itinerary-links"><a class="callToAction" data-objectid="' + neighborhoodObject.OBJECT_ID + '" href="javascript://">' + globalDataInteractionObj.utilities.lang('review') + '</a></div>';
                  //console.log(currentNeighborhoodScore);
                  currentAreaScore = currentNeighborhoodScore + currentAreaScore;

                } else if (currentNeighborhoodStatus == 'Started') {
                  neighborhoodRow += '<div class="col-md-3 itinerary-score">' + globalDataInteractionObj.utilities.lang('inprogress') + '';
                  /*
                   neighborhoodRow += '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 33 100 50">';
                   neighborhoodRow += '<text x="25" y="60" fill="black" font-size="8">IN PROGRESS</text>';
                   neighborhoodRow += '</svg>';
                   */
                  neighborhoodRow += '</div>';
                  neighborhoodRow += '<div class="col-md-2 itinerary-links"><a class="callToAction" data-objectid="' + neighborhoodObject.OBJECT_ID + '" href="javascript://">' + globalDataInteractionObj.utilities.lang('continue') + '</a></div>';
                } else {
                  neighborhoodRow += '<div class="col-md-3 itinerary-score">';
                  /*
                   neighborhoodRow += '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 33 100 50"></svg>';
                   */
                  neighborhoodRow += '</div>';
                  neighborhoodRow += '<div class="col-md-2 itinerary-links"></div>';
                }
                neighborhoodRow += '</div></div>';

              }
            });
            $(accordionBody).append(neighborhoodRow);

          }
          neighborhoodRow += '</div>';

          var numberOfNeighborhoods = (neighborhoods && neighborhoods.length > 0) ? neighborhoods.length : 1;
          var thisAreaScore = Math.round(Number(currentAreaScore) / numberOfNeighborhoods).toFixed();
          var areaClass = 'current-area-score-' + areaObject.OBJECT_ID;
          var areaAvg = 'area-avg-score-' + areaObject.OBJECT_ID;
          if (84 < thisAreaScore) {
            //console.log(thisAreaScore + 'Over 85');
            $('.' + areaAvg).css('background-color', 'rgb(100,196,151)');
            $('.' + areaClass).html(thisAreaScore + '&#37;');
            $('.area-title').css({ 'color': 'rgb(100,196,151)' });
            $('.area-name').css({ 'color': 'rgb(100,196,151)' });
          } else {
            //console.log(thisAreaScore + 'Under 85');
            $('.' + areaAvg).css('background-color', 'rgb(247,79,61)');
            $('.' + areaClass).html(thisAreaScore + '&#37;');
          }
        }

      });

      $(accordionBody).find('a.callToAction').click(function () {
        var learningObjectId = $(this).attr("data-objectid");
        globalDataInteractionObj.utilities.launchNeighborhoodCourse(learningObjectId);
      });
    },
    myProfileTabEventHandler: function () {
      //$('.collapse').on('shown.bs.collapse', function(){
      //$(this).find(".glyphicon-plus").removeClass("glyphicon-plus").addClass("glyphicon-minus");
      //}).on('hidden.bs.collapse', function(){
      //$(this).find(".glyphicon-minus").removeClass("glyphicon-minus").addClass("glyphicon-plus");
      //});

      $('.btn-toggle').on('click', function () {
        $('.is-open').removeClass('glyphicon-minus').addClass('glyphicon-plus');
        var isExpanded = $(this).attr('data-target'),
          textViewable = $(isExpanded).hasClass('in'),
          toggleElem = $(this).find('.is-open');
        toggleElem.removeClass('glyphicon-plus, glyphicon-minus');
        textViewable ? toggleElem.addClass('glyphicon-plus') : toggleElem.addClass('glyphicon-minus');
      });

      $('.btn-toggle-sub').on('click', function () {
        $('.is-open-sub').removeClass('glyphicon-minus').addClass('glyphicon-plus');
        var isExpanded = $(this).attr('data-target'),
          textViewable = $(isExpanded).hasClass('in'),
          toggleElem = $(this).find('.is-open-sub');
        toggleElem.removeClass('glyphicon-plus, glyphicon-minus');
        textViewable ? toggleElem.addClass('glyphicon-plus') : toggleElem.addClass('glyphicon-minus');
      });

    },
    fetchUserObjectRecords: function (callback) { //Fetches records for educational content sessions and returns an array
      var parameters = {
        "type": "userobjectrecords"
      };
      var infiniteLoop = 0;
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api'))
        .done(function (response) {
          //if(infiniteLoop == 0) {
            if (response.hasOwnProperty("DATA")) {
              globalDataInteractionObj.userData.userLearningObjects = response.DATA;
              //console.log("fetchUserObjectRecords "+infiniteLoop, globalDataInteractionObj.userData);
              if (typeof(callback) == "function") {
                callback();
              }
            }
            infiniteLoop += 1;
          //}
      });
    },
    fetchUserObjectRecord: function (currentObjectId, callback) {
      var parameters = {
        "type": "userobjectrecords",
        "object_id": currentObjectId
      };

      var userObjectRecord = false;
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function (response, textStatus, jqHXR) {
        if (response.hasOwnProperty("STATUS")) {
          if (response.STATUS == true) {
            if (response.hasOwnProperty("DATA")) {
              userObjectRecord = response.DATA[0];
            }
            if (typeof(callback) == "function") {
              callback(userObjectRecord);
            }
          } else {
            $('a#mapCallToAction').css("pointer-events", "auto");
          }
        }
      });
    },
    createUserObjectRecord: function (currentObject, callback) {
      var parameters = {};

      parameters = {
        "type": "createuserobjectrecord",
        "object_id": currentObject.OBJECT_ID,
        "status": "Started",
        "parent_object_id": currentObject.OBJECT_PARENT_IDS
      };
      //console.log(parameters);

      var userObjectRecord = false;
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function (response) {
        //console.log("userObjectRecord", response);
        if (response.hasOwnProperty("DATA")) {
          userObjectRecord = response.DATA;
        }
        if (typeof(callback) == "function") {
          callback(userObjectRecord);
        }
      });

    },
    updateUserObjectRecord: function (recordId, status, callback) {
      var parameters = {};

      parameters = {
        "type": "updateuserobjectrecord",
        "record_id": recordId,
        "status": status
      };

      var userObjectRecord = false;
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function (response) {
        //console.log("updateUserObjectRecord", response);
        if (response.hasOwnProperty("DATA")) {
          userObjectRecord = response.DATA;
        }
        if (typeof(callback) == "function") {
          callback(userObjectRecord);
        }
      });
    },
    updateUserObjectRecordStatusByObjectId: function (objectId, status, callback) {
      if (typeof(objectId) != 'undefined' && objectId != null) {
        var objectRecord = globalDataInteractionObj.utilities.findOneByProperty(globalDataInteractionObj.userData.userLearningObjects, 'OBJECT_ID', objectId);
        //console.log(recordObject);
        if (typeof(objectRecord) == 'object' && objectRecord != null) {
          if (objectRecord.hasOwnProperty("RECORD_ID")) {
            globalDataInteractionObj.userData.updateUserObjectRecord(objectRecord.RECORD_ID, status, callback);
          }
        }
      }
    },
    getUserObjectRecordStatus: function (objectId) {
      var userObjectRecordStatus = 'locked';
      if (typeof(objectId) != 'undefined' && objectId != null) {
        var objectRecord = globalDataInteractionObj.utilities.findOneByProperty(globalDataInteractionObj.userData.userLearningObjects, 'OBJECT_ID', objectId);
        //console.log(objectRecord);
        if (typeof(objectRecord) == 'object' && objectRecord != null) {
          if (objectRecord.hasOwnProperty("STATUS")) {
            userObjectRecordStatus = objectRecord.STATUS;
          }
        }
      }
      return userObjectRecordStatus;
    },
    getUserObjectRecordScore: function (objectId) {
      var userObjectRecordScore = '';
      if (typeof(objectId) != 'undefined' && objectId != null) {
        var objectRecord = globalDataInteractionObj.utilities.findOneByProperty(globalDataInteractionObj.userData.userLearningObjects, 'OBJECT_ID', objectId);
        //console.log(objectRecord);
        if (typeof(objectRecord) == 'object' && objectRecord != null) {
          if (objectRecord.hasOwnProperty("SCORE")) {
            userObjectRecordScore = objectRecord.SCORE;
          }
        }
      }
      return userObjectRecordScore;
    },
    setUserLanguage: function () {
      var userDataResponse = globalDataInteractionObj.objectProperties.rawAjaxUserData;
      if (userDataResponse.hasOwnProperty("DATA")) {
        var languageObject = globalDataInteractionObj.utilities.findOneByProperty(elanLanguages, 'id', userDataResponse.DATA.LANGUAGE_ID);
        if (typeof(languageObject) != "undefined" && languageObject != null) {
          globalDataInteractionObj.objectProperties.apiUserLanguage = languageObject;
        }
      }
    },
    setLanguageCommonTerms: function () {
      var userDataResponse = globalDataInteractionObj.objectProperties.rawAjaxUserData;
      if (userDataResponse.hasOwnProperty("DATA")) {
        if (userDataResponse.DATA.hasOwnProperty("CUSTOM_ATTRIBUTES")) {
          if (userDataResponse.DATA.CUSTOM_ATTRIBUTES.hasOwnProperty("CURRENT_MONTH_TAG")) {
            var d = new Date();
            var n = d.getMonth();
            /* TO BE USED IF THERE IS A FIXED MONTH
             if(n == 2){
             current_month_tag = "March";
             }
             else {
             var current_month_tag = userDataResponse.DATA.CUSTOM_ATTRIBUTES.CURRENT_MONTH_TAG;
             }
             */
            var current_month_tag = userDataResponse.DATA.CUSTOM_ATTRIBUTES.CURRENT_MONTH_TAG;
            current_month_tag = current_month_tag.toLowerCase();
            var landing_page_heading = globalDataInteractionObj.utilities.lang('whats_' + current_month_tag);
            $('div.landing-page-heading h1').text(landing_page_heading);
            var current_month = globalDataInteractionObj.utilities.lang(current_month_tag).toLowerCase();
            $('div.associate-completion span.current-month').text(current_month);
          }
        }
      }
    },
    setUserMapLanguage: function (mlRankMap) {
      var abbreviation = globalDataInteractionObj.objectProperties.apiUserLanguage.abbreviation;
      var dt = '?' + new Date().getTime();

      // Load CSS for a particular map
      var mapCssName = mlRankMap + '.config.css';
      var mapCssPath = globalDataInteractionObj.objectProperties.appRoot + "js/jqvmap/" + mapCssName + dt;
      var mapCssRef = document.createElement("link");
      mapCssRef.setAttribute("rel", "stylesheet");
      mapCssRef.setAttribute("type", "text/css");
      mapCssRef.setAttribute("href", mapCssPath);
      document.getElementsByTagName("head")[0].appendChild(mapCssRef);

      // Load CSS for a particular map language
      var mapLangCssName = mlRankMap + '.' + abbreviation + '.css';
      var mapLangCssPath = globalDataInteractionObj.objectProperties.appRoot + "js/jqvmap/i18n/" + mapLangCssName + dt;
      var mapLangCssRef = document.createElement("link");
      mapLangCssRef.setAttribute("rel", "stylesheet");
      mapLangCssRef.setAttribute("type", "text/css");
      mapLangCssRef.setAttribute("href", mapLangCssPath);
      document.getElementsByTagName("head")[0].appendChild(mapLangCssRef);

      // Load JSON data for a particular map and language
      var mapLangFileName = mlRankMap + '.' + abbreviation + '.json';
      var mapLangFilePath = globalDataInteractionObj.objectProperties.appRoot + "js/jqvmap/i18n/" + mapLangFileName + dt;
      $.getJSON(mapLangFilePath, function (data) {
        globalDataInteractionObj.userData.userMapHandler.additionalContent = data.additionalContent;
      });
    },
    setUserMapConfig: function (mlRankMap, callback) {
      // Load Javascript config for a particular map
      var mapConfigJsName = mlRankMap + '.config.js';
      var mapConfigJsPath = globalDataInteractionObj.objectProperties.appRoot + "js/jqvmap/" + mapConfigJsName;
      var mapConfigJsRef = document.createElement("script");
      mapConfigJsRef.setAttribute("type", "text/javascript");
      mapConfigJsRef.setAttribute("charset", "utf-8");
      mapConfigJsRef.setAttribute("async", "false");
      mapConfigJsRef.setAttribute("src", mapConfigJsPath);
      mapConfigJsRef.onload = function () {
        if (typeof(callback) == "function") {
          callback(mlRankMap);
        }
      }
      document.getElementsByTagName("head")[0].appendChild(mapConfigJsRef);
    },

    mlRankMap: function() {
      var mlRankMap = 'mlapprentice';

        var userDataResponse = globalDataInteractionObj.objectProperties.rawAjaxUserData;
        if (userDataResponse.hasOwnProperty("DATA")) {
          if (userDataResponse.DATA.hasOwnProperty("CUSTOM_ATTRIBUTES")) {
            if (userDataResponse.DATA.CUSTOM_ATTRIBUTES.hasOwnProperty("ML_RANK_MAP")) {
              mlRankMap = userDataResponse.DATA.CUSTOM_ATTRIBUTES.ML_RANK_MAP;
            }
          }

      }
      return mlRankMap;
    },

    setUserMapHandler: function (callback, newMap) {
      //console.log('______***______setUserMapHandler________***_______', newMap);
      var mlRankMap = newMap || globalDataInteractionObj.userData.mlRankMap();

      // Load Javascript for a particular map
      var mapJsName = 'jquery.vmap.' + mlRankMap + '.js';
      var mapJsPath = globalDataInteractionObj.objectProperties.appRoot + "js/jqvmap/" + mapJsName;
      var mapJsRef = document.createElement("script");
      mapJsRef.setAttribute("type", "text/javascript");
      mapJsRef.setAttribute("charset", "utf-8");
      mapJsRef.setAttribute("async", "false");
      mapJsRef.setAttribute("src", mapJsPath);
      mapJsRef.onload = function () {
        globalDataInteractionObj.userData.setUserMapConfig(mlRankMap, callback);
      }
      document.getElementsByTagName("head")[0].appendChild(mapJsRef);
    },
    updateProfile: function () {
      globalDataInteractionObj.userData.displayTargetArrival();
      globalDataInteractionObj.userData.displayNextDestination();
      globalDataInteractionObj.userData.displayPassportStamps();
      globalDataInteractionObj.userData.displayJourneyAccordion();
    },
    update: function () {
      globalDataInteractionObj.userData.fetchUserObjectRecords(function () {
        //To set pins on map and display neighborhood as per status and unlock order
        globalDataInteractionObj.mapInteractionHandler.currentArea = null;
        globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = null;
        var nextNeighborhood = globalDataInteractionObj.mapInteractionHandler.getNeighborhoodObjectStatus();
        var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
        globalDataInteractionObj.mapInteractionHandler.displayAreaData(currentArea);
        globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData(nextNeighborhood);
        //Highlight current area
        var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
        var neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', nextNeighborhood, 'NEIGHBORHOOD');
        globalDataInteractionObj.mapInteractionHandler.doEventChanges('autoHash', neighborhoodInfo.parent_url_title, neighborhoodInfo.parent_name);

        //Update data on profile page
        globalDataInteractionObj.userData.updateProfile();
      });
    },
    //version 2 API call - adding fail scenarios
    userInit: function () {
      globalDataInteractionObj.userData.checkCookie(); //<<< this will fail in most browsers
      globalDataInteractionObj.userData.fetchUserObjectRecords();
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher(this.type);
    },
    userUpdate: function() {
      globalDataInteractionObj.userData.fetchUserObjectRecords();
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher(this.type);
    }
  },
  //container for the course data (CLO) interactions. The current design assumes that, upon a successful login, a user's
  //course progress will be sent without a direct dependency to a user's data (i.e. the web browser will not have to
  //depend on passing user data as a parameter for the course data call)
  courseData: {
    type: 'usercoursedata',
    /*
     *  This method is weird. To avoid adding a new plugin to the stack for a carousel, the app will use Twitter
     *  Bootstrap's carousel...but to get three items in one slide, the DOM has to be dynamically appended. For each
     *  element in the carousel with the class, 'item', this method will evaluate the parent (item), get the content
     *  inside the next 'item' elements and append the selected element. CSS controls the display.
     */
    carouselSequence: function () {
      $('.carousel .item').each(function () {
        var next = $(this).next();
        if (!next.length) {
          next = $(this).siblings(':first');
        }
        next.children(':first-child').clone().appendTo($(this));
        if (next.next().length > 0) {
          next.next().children(':first-child').clone().appendTo($(this));
        }
        else {
          $(this).siblings(':first').children(':first-child').clone().appendTo($(this));
        }
      });
    },
    courseInit: function () {
      if (document.getElementById('course-data-summary-template')) {
        globalDataInteractionObj.ajaxUtilities.ajaxLauncher(this.type);
      }
    }
  },
  //container for the course detail data interactions, almost always triggered by a click event on the course detail
  //in the view.
  courseDetail: {
    //This is a DOM traversal method used to get matching presentation elements into course detail overlay. Depending
    //on the API call, this method may need to be refactored, or removed entirely if all of the presentation data can
    //come from the services
    type: 'coursedetaildata',
    getMatchingCourseAttributes: function (elemIdIn) {
      var jQselector = "#" + elemIdIn,
        uiAttrElem = $(jQselector).parent().siblings('.attr-container'),
        selectedImg = uiAttrElem.children('img').attr('src'),
        courseTitleContainer = $(jQselector).parent().siblings('.course-title-container'),
        sectionHeadingContainer = $(jQselector).parent().siblings('.course-type-container'),
        sectionHeadingTitle = sectionHeadingContainer.children('h3').text(),
        courseTitle = courseTitleContainer.children('h4').text();
      courseTitle = courseTitle.replace(/\(/g, '<br/> (');
      $('.course-heading').append('<h3>' + sectionHeadingTitle + '</h3>');
      $('#imgWrapper').append('<img src=' + selectedImg + ' />');
      $('#traversalData').append('<h4>' + courseTitle + '</h4>');
    },
    //This is ugly, but functional, for launch, find a more efficient way to get specific detail data.
    getDetailContentById: function (dataResponse, dataElem) {
      var dataOut;
      for (var index in dataResponse) {
        var selector = dataResponse[index].course_id;
        if (dataElem == selector) {
          dataOut = dataResponse[index];
        }
      }
      return dataOut;
    },
    setUpOverlay: function (element) {
      $(element).removeClass('fade-out-trigger').addClass('fade-in-trigger');
    },
    prepOverlayTeardown: function (element) {
      $(element).addClass('fade-out-trigger').removeClass('fade-in-trigger');
      setTimeout(function () {
        $(element).empty();
        $('.class-link').removeClass('child-open');
        $('.class-link').removeClass('active');
      }, 750);
      monthly_focus.class_link = false;
      $('body').removeClass('modal-open');
    },
    //this init function is driven by a user click event. Each clickable 'Explore' link should display the associated course data.
    eventInit: function () {
      $('.misc-link').on('click', function () {
        //console.log("click-"+globalDataInteractionObj.courseDetail.type+'-'+this.id);
        globalDataInteractionObj.ajaxUtilities.ajaxLauncher(globalDataInteractionObj.courseDetail.type, this.id);
      });
      var classFlag = false;
      $('.class-link').on('click', function () {
        // //console.log('class-link clicked');
        $('.course-detail').show();
        $(this).addClass('active');

        if (!$(this).hasClass('child-open')) {
          // //console.log($(this).attr('attr-link'));
          if ($(this).attr('attr-link') != "") {
            window.open($(this).attr('attr-link'), '_blank');
            var object_id = $(this).attr('id');
            globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updatecreateuserrecord', object_id, '', 'Complete');
            $(this).addClass('open');
          } else {
            var children = $(this).attr('attr-children');
            var data_location = $(this).attr('attr-location').split('-');
            var parent_id = $(this).attr('id');
            $('div.pre-load-modal').fadeIn(300);
            //console.log(globalDataInteractionObj.objectProperties.rawAjaxCourseData[data_location[0]][data_location[1]]);
            monthly_focus.location = data_location;
            monthly_focus.class_link = true;
            $('.class-link').addClass('child-open');
            globalDataInteractionObj.ajaxUtilities.fillCourseDetailModal(globalDataInteractionObj.objectProperties.rawAjaxCourseData[data_location[0]][data_location[1]], parent_id);
          }
          classFlag = true;
          // //console.log('class-link ran - '+classFlag);
          setTimeout(function () {
            classFlag = false;
          }, 500);
        }

        //console.log('class-link');
        return false;
      });

      $('.misc-nolink').on('click', function () {
        window.open('https://www.instagram.com/coach/', '_blank');
      });
    }
  },
  //container for the event-driven interactions in the global footer and left navigation
  interactionHandler: {
    setHeightForObjectProperty: function (propertyIn, heightIn) {
      propertyIn = heightIn;
    },
    //this init should be called after the ajax content has loaded. For now, this main method is wrapped in a setTimeout function
    //TODO: Rewrite with ES6 promises???? (Might want to restructure all of the event handlers if we go this route)
    subInit: function () {
      $(window).on('resize', function (e) {
        clearTimeout(resizer);
        //Set height for map content
        globalDataInteractionObj.mapInteractionHandler.setContentHeight();
        resizer = setTimeout(function () {
          globalDataInteractionObj.interactionHandler.setHeightForObjectProperty(globalDataInteractionObj.objectProperties.viewportHeight, document.documentElement.clientHeight);

          // $(window).scrollTop(0); // removed because it created UX bugs on devices

          //Set height for map content
          globalDataInteractionObj.mapInteractionHandler.setContentHeight();

        }, 250);
      });
    }
  },
  //container for the event-driven interactions in the journey map
  mapInteractionHandler: {
    mapLearningObjects: {},
    currentJQVMapObject: {},
    currentMapObject: {},
    currentMap: null,
    currentMapObjectId: null,
    currentArea: null,
    currentAreaObjectId: null,
    currentNeighborhood: null,
    currentNeighborhoodObjectId: null,
    loadSlideOutPanel: function () {
      var stopLoadPropagation = 0;
      $('div.journey-content').load(globalDataInteractionObj.ajaxUtilities.addAjaxPathIndex('journey'), null, function () {
        //console.log('LOAD~~~~~~~~~~~~~~~~~loadSlideOutPanel', stopLoadPropagation);
        if (stopLoadPropagation == 0) {
          // var windowHeight = $(window).height();
          // var globalFooterHeight = $("footer#main-footer").height();
          // var contentHeight = windowHeight - globalFooterHeight;
          // //$( "#main_content" ).css( "height", contentHeight);
          // $("div.journey-content").css("height", contentHeight);
          // $("div.map-container").css("height", contentHeight);
          // $("div#vmap").css("height", contentHeight);
          // $("div.map-info-container").css("height", contentHeight);
          // $("div.map-info").css("height", contentHeight);
          // globalDataInteractionObj.ajaxUtilities.loading('div.map-info');
          globalDataInteractionObj.mapInteractionHandler.mapDataInit();
          globalDataInteractionObj.mapInteractionHandler.setMapInfoHeight();
          stopLoadPropagation += 1;
        }
      });
    },
    setMapInfoHeight: function () {
      var windowHeight = $(window).height();
      var globalFooterHeight = $("footer#main-footer").height();
      var contentHeight = windowHeight - globalFooterHeight;
      //$( "#main_content" ).css( "height", contentHeight);
      $("div.journey-content").css("height", contentHeight);
      $("div.map-container").css("height", contentHeight);
      $("div#vmap").css("height", contentHeight);
      $("div.map-info-container").css("height", contentHeight);
      $("div.map-info").css("height", contentHeight);
      $("div#vmap svg").first().height(contentHeight);
    },
    setContentHeight: function () {

      var is_iPad = globalDataInteractionObj.objectProperties.isIPad;
      //alert(is_iPad);

      //Set width for map content to set pins on proper position
      var journeyContentWidth = $("div#mapJourney").width();
      if (journeyContentWidth > 0) {
        var mapInfoWidth = $("div.journey-content div.map-info-container").width();
        var mapWidth = journeyContentWidth - mapInfoWidth;
        if (journeyContentWidth <= 1800) {
          mapWidth = mapWidth + 30;
        }
        if (mapWidth < 759) {
          mapWidth = 759;
        }
        //alert('mapWidth: ' + mapWidth);
        $("div#vmap svg").first().width(mapWidth);
      }

      //Set height for map content
      // var windowHeight = $(window).height();
      // var globalFooterHeight = $("footer#main-footer").height();
      // var contentHeight = windowHeight - globalFooterHeight;
      // //$( "#main_content" ).css( "height", contentHeight);
      // $("div.journey-content").css("height", contentHeight);
      // $("div.map-container").css("height", contentHeight);
      // $("div#vmap").css("height", contentHeight);
      // $("div.map-info-container").css("height", contentHeight);
      // $("div.map-info").css("height", contentHeight);

      // $("div#vmap svg").first().height(contentHeight);
      //alert($( "div#vmap svg" ).height());
      globalDataInteractionObj.mapInteractionHandler.setMapInfoHeight();
      /*
       var extraHeight = 0;

       if (is_iPad) {
       extraHeight += $("div.map-info img.main-image").height();
       if (extraHeight <= 0) {
       extraHeight = 213;
       }
       } else {
       extraHeight += $("div.map-info img.main-image").height();
       }
       //console.log('extraHeight:' + extraHeight);
       //alert('extraHeight:' + extraHeight);
       extraHeight += $("div.map-info div.map-heading").height() + 9;
       //console.log('extraHeight:' + extraHeight);
       //alert('extraHeight:' + extraHeight);
       extraHeight += $("div.map-info div.panel-heading").height() + 19;
       //console.log('extraHeight:' + extraHeight);
       //alert('extraHeight:' + extraHeight);
       if ($("#main-footer").height() != null) {
       extraHeight += $("#main-footer").height();
       extraHeight += $(".map-heading").height();
       extraHeight += $(".panel-heading").height();
       } else {
       extraHeight += 10;
       }
       //console.log('extraHeight:' + extraHeight);
       //alert('extraHeight:' + extraHeight);
       var panelBodyHeight = contentHeight - extraHeight;
       $("div.map-info div.panel-body").css("height", panelBodyHeight);
       */
    },
    getNeighborhoodObjectStatus: function (neighborhoodObjectId) {
      var currentAreaId = '';
      var currentAreaStatus = '';
      var currentNeighborhoodId = '';
      var currentNeighborhoodStatus = '';
      var currentNeighborhoodTagName = '';
      var previousNeighborhoodStatus = '';
      var previousNeighborhoodTagName = '';
      if (!neighborhoodObjectId || neighborhoodObjectId == "") {
        neighborhoodObjectId = 0;
      }
      //console.log('neighborhoodObjectId: ' + neighborhoodObjectId);
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var elanMapUnlockObjectId = globalDataInteractionObj.mapInteractionHandler.getElanMapUnlockObjectId();

      var areas = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', '0');
      areas = globalDataInteractionObj.utilities.sortByProperty(areas, 'unlockorder');
      //console.log("getNeighborhoodObjectStatus areas", areas);

      var neighborhoods = [];
      if (areas.length > 0) {
        $.each(areas, function (akey, avalue) {
          //console.log(avalue.cat_url_title);
          var areaObject = globalDataInteractionObj.utilities.findMapArea(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, avalue.cat_name);
          //console.log(areaObject);
          if (typeof(areaObject) == 'object' && areaObject != null) {
            currentAreaId = areaObject.OBJECT_ID;
          }
          currentAreaStatus = 'locked';
          if (currentAreaId != '') {
            currentAreaStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(currentAreaId);
          }
          //console.log('Area: ' + currentAreaId + ' - ' + currentAreaStatus);

          neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', avalue.cat_id);
          neighborhoods = globalDataInteractionObj.utilities.sortByProperty(neighborhoods, 'unlockorder');

          //console.log(neighborhoods);

          if (neighborhoods.length > 0) {
            $.each(neighborhoods, function (nkey, nvalue) {
              //console.log(nvalue.cat_name);
              var neighborhoodObject = globalDataInteractionObj.utilities.findMapNeighborhood(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, nvalue.cat_name);
              //console.log(neighborhoodObject);
              if (typeof(neighborhoodObject) == 'object' && neighborhoodObject != null) {
                currentNeighborhoodId = neighborhoodObject.OBJECT_ID;
              }

              currentNeighborhoodTagName = nvalue.cat_name;
              currentNeighborhoodStatus = 'locked';
              if (currentNeighborhoodId != '') {
                currentNeighborhoodStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(currentNeighborhoodId);
              }

              if (currentNeighborhoodStatus == 'locked' && (previousNeighborhoodStatus == 'Complete' || previousNeighborhoodStatus == 'Passed')) {
                currentNeighborhoodStatus = 'open';
              }

              // By default open first neighborhood
              if (currentNeighborhoodId == elanMapUnlockObjectId && currentNeighborhoodStatus == 'locked') {
                currentNeighborhoodStatus = 'open';
              }
              //console.log('Neighborhood: ' + currentNeighborhoodId + ' - ' + currentNeighborhoodStatus);

              if (neighborhoodObjectId == 0) {
                var pinType = '';
                if (currentNeighborhoodStatus == 'open' || currentNeighborhoodStatus == 'Started') {
                  if (currentNeighborhoodStatus == 'Started') {
                    pinType = 'progress';
                  }
                  var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
                  //console.log('currentArea: ' + currentArea);
                  if (typeof(currentArea) == 'undefined' || currentArea == null) {
                    globalDataInteractionObj.mapInteractionHandler.currentArea = avalue.cat_name;
                    globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = nvalue.cat_name;
                  }
                } else if (currentNeighborhoodStatus == 'Complete' || currentNeighborhoodStatus == 'Passed') {
                  pinType = 'complete';
                }
                if (pinType != '') {
                  var pinImageName = 'pin-' + pinType + '-' + avalue.cat_url_title + '.png';
                  var pingImagePath = globalDataInteractionObj.objectProperties.appRoot + "images/marker/" + pinImageName;
                  var pinContent = "<div class='map-pin'><span class='pin-img' ";
                  pinContent += "style=\"background-image: url('" + pingImagePath + "')\"";
                  pinContent += ">";
                  pinContent += "</span></div>";
                  globalDataInteractionObj.mapInteractionHandler.setPin(nvalue.cat_url_title, pinContent);
                }
              }

              //console.log(previousNeighborhoodTagName);
              if (currentNeighborhoodStatus == 'open' && previousNeighborhoodTagName != '') {
                var previousNeighborhoodAssessmentObject = globalDataInteractionObj.utilities.findMapNeighborhoodAssessment(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, previousNeighborhoodTagName);
                //console.log(previousNeighborhoodAssessmentObject);
                if (typeof(previousNeighborhoodAssessmentObject) != 'undefined' && previousNeighborhoodAssessmentObject != null) {
                  var previousNeighborhoodAssessmentStatus = globalDataInteractionObj.userData.getUserObjectRecordStatus(previousNeighborhoodAssessmentObject.OBJECT_ID);
                  //console.log(previousNeighborhoodTagName + ' -> ' + previousNeighborhoodAssessmentStatus);
                  if (previousNeighborhoodAssessmentStatus != 'Complete') {
                    currentNeighborhoodStatus = 'locked';
                  }
                }
              }
              //console.log(previousNeighborhoodStatus + ' -> ' + currentNeighborhoodStatus);

              previousNeighborhoodStatus = currentNeighborhoodStatus;
              previousNeighborhoodTagName = currentNeighborhoodTagName;

              // Exit from Neighborhoods loop
              if (currentNeighborhoodId == neighborhoodObjectId) {
                return false;
              }
            });
          }
          // Exit from Areas loop
          if (currentNeighborhoodId == neighborhoodObjectId) {
            return false;
          }

          // Mark area is complete if all neighborhoods are complete
          if (neighborhoodObjectId == 0 && currentAreaId != '' && currentAreaStatus == 'Started' && currentNeighborhoodStatus == 'Complete') {
            globalDataInteractionObj.userData.updateUserObjectRecordStatusByObjectId(currentAreaId, 'Complete');
          }
        });
      }

      if (neighborhoodObjectId == 0) {
        globalDataInteractionObj.mapInteractionHandler.displayPins();
        return globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
      } else {
        return currentNeighborhoodStatus;
      }
    },
    fetchMapLearningObjects: function (mapTagName) {
      var parameters = {};

      if (!mapTagName) {
        mapTagName = globalDataInteractionObj.mapInteractionHandler.currentMap;
      }
      mapTagName += ',English';
      parameters = {
        "type": "learninginfo",
        "tags": mapTagName
      };

      //'local', 'multiplelearningobjects'
      //$.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'local', 'multiplelearningobjects'))
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api'))
        .done(function (response) {
          if (response && response.hasOwnProperty("DATA")) {
            //console.log("fetchMapLearningObjects", response.DATA);
            globalDataInteractionObj.mapInteractionHandler.mapLearningObjects = response.DATA;
            globalDataInteractionObj.mapInteractionHandler.setElanMapObjectId();
            globalDataInteractionObj.mapInteractionHandler.setElanMapUnlockObjectId();
            //console.log(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects);
            globalDataInteractionObj.mapInteractionHandler.preLoadLearningObjectImages();
            globalDataInteractionObj.mapInteractionHandler.displayCurrentData();
          }
        }).fail(function (jqxhr, status) {
        //console.log(jqxhr + ' status: ' + status);
      });
    },
    preLoadLearningObjectImages: function () {
      var mapLearningObjects = globalDataInteractionObj.mapInteractionHandler.mapLearningObjects;
      $.each(mapLearningObjects, function (key, learningObject) {
        $('<img/>')[0].src = learningObject.OBJECT_IMAGE;
      });
    },
    setCurrentObject: function (currentLevel, currentObject) {
      currentObject.OBJECT_LEVEL = currentLevel;
      currentObject.OBJECT_PARENT_IDS = "";
      if (currentLevel == "MAP") {
        globalDataInteractionObj.mapInteractionHandler.currentMapObjectId = currentObject.OBJECT_ID;
        globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId = null;
        globalDataInteractionObj.mapInteractionHandler.currentNeighborhoodObjectId = null;
      } else if (currentLevel == "AREA") {
        globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId = currentObject.OBJECT_ID;
        globalDataInteractionObj.mapInteractionHandler.currentNeighborhoodObjectId = null;
        currentObject.OBJECT_PARENT_IDS = globalDataInteractionObj.mapInteractionHandler.currentMapObjectId;
      } else if (currentLevel == "NEIGHBORHOOD") {
        globalDataInteractionObj.mapInteractionHandler.currentNeighborhoodObjectId = currentObject.OBJECT_ID;
        currentObject.OBJECT_PARENT_IDS = globalDataInteractionObj.mapInteractionHandler.currentMapObjectId;
        currentObject.OBJECT_PARENT_IDS = currentObject.OBJECT_PARENT_IDS + '|' + globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId;
      }
      globalDataInteractionObj.mapInteractionHandler.currentMapObject = currentObject;
    },
    findMapObject: function (mapTagName) {
      //console.log("***findMapObject", mapTagName);
      var group_url_title = (globalDataInteractionObj.mapInteractionHandler.currentMap == 'EU') ? 'mlambassador' : 'mlapprentice';
      if (typeof(mapTagName) == 'undefined' || mapTagName == null) {
        mapTagName = globalDataInteractionObj.mapInteractionHandler.currentMap;


        var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
        //console.log("***currentMapInfo", currentMapInfo);
        var mapInformation = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'group_name', mapTagName);
        group_url_title = mapInformation.group_url_title;
      }

      //console.log(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects);
      var elanMapObjectId = globalDataInteractionObj.mapInteractionHandler.getElanMapObjectId();
      var mapObject = globalDataInteractionObj.utilities.findOneByProperty(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, 'OBJECT_ID', elanMapObjectId);
      if (typeof(mapObject) == 'object' && mapObject != null) {
        mapObject.OBJECT_TAG = mapTagName;
        mapObject.OBJECT_CLASS_NAME = globalDataInteractionObj.utilities.createClassName(mapObject.TAGS);
        mapObject.OBJECT_TAG_LANG = globalDataInteractionObj.utilities.lang(group_url_title);
        mapObject.OBJECT_HELP = true;
        mapObject.OBJECT_SHOW_INFO = false;
        mapObject.OBJECT_SHOW_NAV = false;
        mapObject.OBJECT_SHOW_MAPS = true;
        //mapObject.OBJECT_HELP_TEXT = mapObject['Help Text'];
        mapObject.OBJECT_HELP_TEXT = '<p class=\'text-right btn-close\'><span class=\'glyphicon glyphicon-remove-circle\' aria-hidden=\'true\'></span></p>';
        mapObject.OBJECT_HELP_TEXT += '<p>' + globalDataInteractionObj.utilities.lang('helptextP1') + '</p>';
        mapObject.OBJECT_HELP_TEXT += '<p>' + globalDataInteractionObj.utilities.lang('helptextP2') + '</p>';
        mapObject.OBJECT_HELP_TEXT += '<p>' + globalDataInteractionObj.utilities.lang('helptextP3') + '</p>';
        mapObject.OBJECT_HELP_TEXT += '<p>' + globalDataInteractionObj.utilities.lang('helptextP4') + '</p>';
        mapObject.OBJECT_HELP_TEXT = globalDataInteractionObj.utilities.escapeXml(mapObject.OBJECT_HELP_TEXT);

        var elanMapUnlockObjectId = globalDataInteractionObj.mapInteractionHandler.getElanMapUnlockObjectId();

        /* START: This whole block with AJAX call exists only to change language of the $('#mapCallToAction') button */
        var userObjectStatus = globalDataInteractionObj.mapInteractionHandler.getNeighborhoodObjectStatus(elanMapUnlockObjectId);

        if (userObjectStatus != 'undefined') {
          if (userObjectStatus != 'locked' && userObjectStatus != 'open') {
            mapObject.CALL_TO_ACTION = globalDataInteractionObj.utilities.lang('continueyourjourney');
          } else {
            mapObject.CALL_TO_ACTION = globalDataInteractionObj.utilities.lang('startyourjourney');
          }
        } else {
          mapObject.CALL_TO_ACTION = globalDataInteractionObj.utilities.lang('comingsoon') || 'COMING SOON';
        }
        /* END: This whole block with AJAX call exists only to change language of the button */

        var userLanguageID = globalDataInteractionObj.objectProperties.apiUserLanguage.id;
        if (userLanguageID != 'undefined') {
          //to change title and description of an area based on selected language circumventing creating multiple SCORM objects for the same Chameleon course
          if (userLanguageID == 1) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.OBJECT_NAME;
            mapObject.NEIGHBORHOOD_DESC = mapObject.OBJECT_DESCR;
          }
		  else if (userLanguageID == 3) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.map_fr_CA_name;
            mapObject.NEIGHBORHOOD_DESC = (mapObject.map_fr_CA_description) ? mapObject.map_fr_CA_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
          }
          else if (userLanguageID == 6) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.map_zh_Hans_name;
            mapObject.NEIGHBORHOOD_DESC = (mapObject.map_zh_Hans_description) ? mapObject.map_zh_Hans_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
          }
          else if (userLanguageID == 11) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.map_zh_Hant_name;
            mapObject.NEIGHBORHOOD_DESC = (mapObject.map_zh_Hant_description) ? mapObject.map_zh_Hant_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
          }
          else if (userLanguageID == 12) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.map_ja_name;
            mapObject.NEIGHBORHOOD_DESC = (mapObject.map_ja_description) ? mapObject.map_ja_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
          }
          else if (userLanguageID == 13) {
            mapObject.NEIGHBORHOOD_TITLE = mapObject.map_ko_name;
            mapObject.NEIGHBORHOOD_DESC = (mapObject.map_ko_description) ? mapObject.map_ko_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
          }
        }
      }
      //console.log(mapObject);
      return mapObject;
    },
    findAreaObject: function (areaTagName) {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      //console.log(currentMapInfo);
      var areas = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', '0');
      //console.log("findAreaObject within", areas);

      if (typeof(areaTagName) == 'undefined' || areaTagName == null) {
        var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
        var areaInfo = {};
        if (typeof(currentArea) != 'undefined' && currentArea != null) {
          areaInfo = globalDataInteractionObj.utilities.findOneByProperty(areas, 'cat_name', currentArea, 'AREA');
        } else {
          areaInfo = globalDataInteractionObj.utilities.sortByProperty(areas, 'unlockorder')[0];
        }

        //console.log(areaInfo);
        areaTagName = areaInfo.cat_name;

        // To highlight area when journey starts or continue
        globalDataInteractionObj.mapInteractionHandler.doEventChanges('autoHash', areaInfo.cat_url_title, areaInfo.cat_name);
      } else {
        var areaInfo = globalDataInteractionObj.utilities.findOneByProperty(areas, 'cat_name', areaTagName, 'AREA');
        //console.log("findAreaObject areaInfo", areaInfo);
      }
      //console.log('areaTagName:' + areaTagName);
      var areaObject = globalDataInteractionObj.utilities.findMapArea(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, areaTagName);
      //console.log("findAreaObject areaObject", areaObject);
      var userLanguageID = globalDataInteractionObj.objectProperties.apiUserLanguage.id;
      if (typeof(areaObject) == 'object' && areaObject != null) {
        areaObject.OBJECT_SHOW_MAPS = false;
        areaObject.OBJECT_TAG = areaTagName;
        areaObject.OBJECT_CLASS_NAME = globalDataInteractionObj.utilities.createClassName(areaObject.TAGS);
        areaObject.OBJECT_TAG_LANG = globalDataInteractionObj.utilities.lang(areaInfo.cat_url_title);
        areaObject.OBJECT_HELP = false;
        areaObject.OBJECT_SHOW_INFO = false;
        areaObject.OBJECT_SHOW_NAV = false;
        areaObject.CALL_TO_ACTION = (globalDataInteractionObj.mapInteractionHandler.currentMap == 'EU') ? (globalDataInteractionObj.utilities.lang('comingsoon') ||'COMING SOON') : globalDataInteractionObj.utilities.lang('enterneighborhoods');
        //to change title and description of an area based on selected language circumventing creating multiple SCORM objects for the same Chameleon course
        if (userLanguageID == 1) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.OBJECT_NAME;
          areaObject.NEIGHBORHOOD_DESC = areaObject.OBJECT_DESCR;
        }
		 else if (userLanguageID == 3) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.map_fr_CA_name;
          areaObject.NEIGHBORHOOD_DESC = (areaObject.map_fr_CA_description) ? areaObject.map_fr_CA_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
        }
        else if (userLanguageID == 6) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.map_zh_Hans_name;
          areaObject.NEIGHBORHOOD_DESC = (areaObject.map_zh_Hans_description) ? areaObject.map_zh_Hans_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
        }
        else if (userLanguageID == 11) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.map_zh_Hant_name;
          areaObject.NEIGHBORHOOD_DESC = (areaObject.map_zh_Hant_description) ? areaObject.map_zh_Hant_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
        }
        else if (userLanguageID == 12) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.map_ja_name;
          areaObject.NEIGHBORHOOD_DESC = (areaObject.map_ja_description) ? areaObject.map_ja_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
        }
        else if (userLanguageID == 13) {
          areaObject.NEIGHBORHOOD_TITLE = areaObject.map_ko_name;
          areaObject.NEIGHBORHOOD_DESC = (areaObject.map_ko_description) ? areaObject.map_ko_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>')+'<p> </p>' : '';
        }
      }
      return areaObject;
    },
    findNeighborhoodObject: function (neighborhoodTagName) {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      //console.log('findNeighborhoodObject '+neighborhoodTagName, currentMapInfo);

      if(globalDataInteractionObj.mapInteractionHandler.currentMap != 'EU') {
        if (typeof(neighborhoodTagName) == 'undefined' || neighborhoodTagName == null) {
          var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_name', globalDataInteractionObj.mapInteractionHandler.currentArea);
          //console.log(neighborhoods);
          var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
          //console.log(currentNeighborhood);
          var neighborhoodInfo = {};
          if (typeof(currentNeighborhood) != 'undefined' && currentNeighborhood != null) {
            neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(neighborhoods, 'cat_name', currentNeighborhood, 'NEIGHBORHOOD');
          } else {
            // Find first neighborhood in current area
            neighborhoodInfo = globalDataInteractionObj.utilities.sortByProperty(neighborhoods, 'unlockorder')[0];
          }
          neighborhoodTagName = neighborhoodInfo.cat_name;
        } else {
          var neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', neighborhoodTagName, 'NEIGHBORHOOD');
          var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_url_title', neighborhoodInfo.parent_url_title);
          //console.log(neighborhoods);
        }
        var neighborhoodObject = globalDataInteractionObj.utilities.findMapNeighborhood(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, neighborhoodTagName);
      } else {
        var neighborhoodObject = globalDataInteractionObj.utilities.findMapArea(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, neighborhoodTagName);
      }
      //console.log('neighborhoodTagName:' + neighborhoodTagName);
      //console.log(neighborhoodObject);
      var userLanguageID = globalDataInteractionObj.objectProperties.apiUserLanguage.id;
      if (typeof(neighborhoodObject) == 'object' && neighborhoodObject != null) {
        neighborhoodObject.OBJECT_TAG = neighborhoodTagName;
        neighborhoodObject.OBJECT_SHOW_MAPS = false;
        neighborhoodObject.OBJECT_CLASS_NAME = globalDataInteractionObj.utilities.createClassName(neighborhoodObject.TAGS);
        neighborhoodObject.LANG_ID = userLanguageID;
        neighborhoodObject.OBJECT_TAG_LANG = globalDataInteractionObj.utilities.lang(neighborhoodInfo.cat_url_title);
        neighborhoodObject.OBJECT_HELP = false;
        neighborhoodObject.OBJECT_SHOW_INFO = false;
        neighborhoodObject.OBJECT_NUMBER_OF_STOPS = neighborhoodInfo.number_of_stops || '';
        neighborhoodObject.OBJECT_MINUTES_TO_FINISH = neighborhoodInfo.minutes_to_finish || '';
        if (neighborhoodObject.OBJECT_NUMBER_OF_STOPS != '' && neighborhoodObject.OBJECT_MINUTES_TO_FINISH != '') {
          neighborhoodObject.OBJECT_SHOW_INFO = true;
          neighborhoodObject.OBJECT_STOPS_IMAGE_CLASS = neighborhoodInfo.parent_url_title;
          neighborhoodObject.OBJECT_MINUTES_IMAGE_CLASS = neighborhoodInfo.parent_url_title;
        }
        if (neighborhoods.length > 1) {
          neighborhoodObject.OBJECT_SHOW_NAV = true;
        } else {
          neighborhoodObject.OBJECT_SHOW_NAV = false;
        }
        //to change title and description of an neighborhood based on selected language circumventing creating multiple SCORM objects for the same Chameleon course
        if (userLanguageID == 1) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.OBJECT_NAME;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.OBJECT_DESCR;
        }
		else if (userLanguageID == 3) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.map_fr_CA_name;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.map_fr_CA_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>');
        }
        else if (userLanguageID == 6) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.map_zh_Hans_name;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.map_zh_Hans_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>');
        }
        else if (userLanguageID == 11) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.map_zh_Hant_name;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.map_zh_Hant_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>');
        }
        else if (userLanguageID == 12) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.map_ja_name;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.map_ja_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>');
        }
        else if (userLanguageID == 13) {
          neighborhoodObject.NEIGHBORHOOD_TITLE = neighborhoodObject.map_ko_name;
          neighborhoodObject.NEIGHBORHOOD_DESC = neighborhoodObject.map_ko_description.replace(/\*\*\*/g, '<br/><br/>').replace(/\<\</g, '<i>').replace(/\>\>/g, '</i>');
        }
      }
      //console.log(neighborhoodObject);
      return neighborhoodObject;
    },
    setObjectPin: function () {
      //Display pin
      //console.log(globalDataInteractionObj.mapInteractionHandler.currentMapObject.OBJECT_LEVEL);
      if (jQuery('#vmap svg').length > 0) {
        var neighborhoodPins = $.extend({}, globalDataInteractionObj.mapInteractionHandler.getPins());
        if (globalDataInteractionObj.mapInteractionHandler.currentMapObject.OBJECT_LEVEL == 'NEIGHBORHOOD') {
          var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
          var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
          var currentNeighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', currentNeighborhood, 'NEIGHBORHOOD');

          var pinImageName = 'pin-select-' + currentNeighborhoodInfo.parent_url_title + '.png';
          var pingImagePath = globalDataInteractionObj.objectProperties.appRoot + "images/marker/" + pinImageName;
          var pinContent = "<div class='map-pin'><span class='pin-img' ";
          pinContent += "style=\"background-image: url('" + pingImagePath + "')\"";
          pinContent += ">";
          pinContent += "</span></div>";
          neighborhoodPins[currentNeighborhoodInfo.cat_url_title] = pinContent;
        }
        globalDataInteractionObj.mapInteractionHandler.displayPins(neighborhoodPins);
      }
    },
    displayPins: function (neighborhoodPins) {
      //console.log(neighborhoodPins);
      if (typeof(neighborhoodPins) == 'undefined') {
        neighborhoodPins = globalDataInteractionObj.mapInteractionHandler.getPins();
      }
      globalDataInteractionObj.mapInteractionHandler.currentJQVMapObject.placePins(neighborhoodPins, 'content');

      //set pins position as per device
      /*
       var is_iPad = globalDataInteractionObj.objectProperties.isIPad;
       if( !is_iPad ) {

       var journeyContentWidth = $( "div.journey-content" ).width();
       //console.log(journeyContentWidth);
       if( journeyContentWidth > 0) {
       var mapInfoWidth = $( "div.journey-content div.map-info-container" ).width();
       //console.log(mapInfoWidth);
       var documentWidth = $( document ).width();
       //console.log(documentWidth);
       if( documentWidth > journeyContentWidth ) {
       var temp = (documentWidth - journeyContentWidth) / 2;
       mapInfoWidth = mapInfoWidth + temp;
       }
       //console.log(mapInfoWidth);
       $( ".map-container div#vmap .jqvmap-pin" ).css( "margin-left", "-"+mapInfoWidth+"px");
       }
       } else {
       var marginLeft = parseInt($( ".map-container div#vmap .jqvmap-pin" ).css( "margin-left")) + 10;
       $( ".map-container div#vmap .jqvmap-pin" ).css( "margin-left", "-"+marginLeft+"px");
       }
       */
    },
    displayObjectInfo: function (objectLevel, objectInfo) {
      globalDataInteractionObj.mapInteractionHandler.setCurrentObject(objectLevel, objectInfo);
      globalDataInteractionObj.handlebarsUtils.setTemplates('map-info-template', objectInfo);

      $('[data-toggle="popover"]').popover({ container: 'body' });
      $('[data-toggle="popover"]').on('shown.bs.popover', function () {
        $('.popover-content .btn-close span').on('click', function () {
          $('[data-toggle="popover"]').popover('hide');
        });
      });

      //Set height for map content
      globalDataInteractionObj.mapInteractionHandler.setContentHeight();
      globalDataInteractionObj.mapInteractionHandler.setObjectPin();
    },
    displayMapData: function (mapTagName) {
      // globalDataInteractionObj.ajaxUtilities.loading('div.map-info');
      var mapObject = globalDataInteractionObj.mapInteractionHandler.findMapObject(mapTagName);
      if (typeof(mapObject) == 'object' && mapObject != null) {
        globalDataInteractionObj.mapInteractionHandler.currentMap = mapObject.OBJECT_TAG;
        globalDataInteractionObj.mapInteractionHandler.displayObjectInfo('MAP', mapObject);

        $('a#mapCallToAction').click(function () {
          var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
          var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
          //console.log('currentArea: ' + currentArea);
          //console.log('currentNeighborhood: ' + currentNeighborhood);
          if (typeof(currentArea) != 'undefined' && currentArea != null
            && typeof(currentNeighborhood) != 'undefined' && currentNeighborhood != null) {
            //This line of code needed to highlight area when journey continue
            var currentAreaObject = globalDataInteractionObj.mapInteractionHandler.findAreaObject();
            globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId = currentAreaObject.OBJECT_ID;
            globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData(currentNeighborhood);
          } else {
            globalDataInteractionObj.mapInteractionHandler.displayAreaData(mapTagName);
          }
        });
      }
    },
    displayAreaData: function (areaTagName) {
      $('[data-toggle="popover"]').popover('hide');
      // globalDataInteractionObj.ajaxUtilities.loading('div.map-info');
      //console.log('areaTagName: ' + areaTagName);
      var areaObject = globalDataInteractionObj.mapInteractionHandler.findAreaObject(areaTagName);
      //console.log("displayAreaData "+areaTagName, areaObject);

      if (typeof(areaObject) == 'object' && areaObject != null) {
        globalDataInteractionObj.mapInteractionHandler.currentArea = areaObject.OBJECT_TAG;
        var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
        var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_name', areaObject.OBJECT_TAG);
        //console.log(neighborhoods);
        //If area has only one neighborhood then display neighborhood
        if (neighborhoods.length == 1) {
          globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId = areaObject.OBJECT_ID;
          globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData();
        } else {
          location.hash = 'journey|' + areaObject.OBJECT_TAG;
          globalDataInteractionObj.mapInteractionHandler.displayObjectInfo('AREA', areaObject);

          $('a#mapCallToAction').click(function () {
            globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData();
          });
        }
      }
    },
    displayNeighborhoodData: function (neighborhoodTagName) {
      if(globalDataInteractionObj.mapInteractionHandler.currentMap == 'EU') {
        //console.log("~~~~~~~~~~~~~~displayNeighborhoodData EU "+globalDataInteractionObj.mapInteractionHandler.currentArea);
        globalDataInteractionObj.utilities.launchNeighborhoodCourse(globalDataInteractionObj.mapInteractionHandler.currentAreaObjectId);
        // TODO: uncomment after demo
        /*       if (userObjectStatus != 'locked') {
          $('a#mapCallToAction').click(function () {
            $('a#mapCallToAction').css("pointer-events", "none");
          });
       }*/
      } else {
        //console.log("displayNeighborhoodData " + neighborhoodTagName);
        // globalDataInteractionObj.ajaxUtilities.loading('div.map-info');

        var neighborhoodObject = globalDataInteractionObj.mapInteractionHandler.findNeighborhoodObject(neighborhoodTagName);
        if (typeof(neighborhoodObject) == 'object' && neighborhoodObject != null) {
          location.hash = 'journey|' + globalDataInteractionObj.mapInteractionHandler.currentArea + '|' + neighborhoodObject.OBJECT_TAG;
          globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = neighborhoodObject.OBJECT_TAG;

          var userObjectStatus = '';
          if (neighborhoodObject.OBJECT_ID == '138') {
            userObjectStatus = 'open';
          } else {
            var userObjectStatus = globalDataInteractionObj.mapInteractionHandler.getNeighborhoodObjectStatus(neighborhoodObject.OBJECT_ID);
          }
          var userLanguageID = globalDataInteractionObj.objectProperties.apiUserLanguage.id;
          //console.log(userLanguageID);
          if (userObjectStatus == 'locked') {
            neighborhoodObject.CALL_TO_ACTION = globalDataInteractionObj.utilities.lang('locked');
          } else {
            neighborhoodObject.CALL_TO_ACTION = globalDataInteractionObj.utilities.lang('experience');
          }


          //console.log(neighborhoodObject);
          globalDataInteractionObj.mapInteractionHandler.displayObjectInfo('NEIGHBORHOOD', neighborhoodObject);

          if (userObjectStatus != 'locked') {
            $('a#mapCallToAction').click(function () {
              $('a#mapCallToAction').css("pointer-events", "none");
              globalDataInteractionObj.utilities.launchNeighborhoodCourse();
            });
          }

          $('a#btn-previous').click(function () {
            globalDataInteractionObj.mapInteractionHandler.previousNeighborhood();
          });

          $('a#btn-next').click(function () {
            globalDataInteractionObj.mapInteractionHandler.nextNeighborhood();
          });
        }
      }
    },
    previousNeighborhood: function () {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
      var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
      var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_name', currentArea);
      neighborhoods = globalDataInteractionObj.utilities.sortByProperty(neighborhoods, 'unlockorder');
      var currentIndex = globalDataInteractionObj.utilities.findIndexByProperty(neighborhoods, 'cat_name', currentNeighborhood);
      var previousIndex = currentIndex - 1;
      if (previousIndex < 0) {
        previousIndex = neighborhoods.length - 1;
      }
      var neighborhoodTagName = neighborhoods[previousIndex].cat_name;
      globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData(neighborhoodTagName);
    },
    nextNeighborhood: function () {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
      var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;
      var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_name', currentArea);
      neighborhoods = globalDataInteractionObj.utilities.sortByProperty(neighborhoods, 'unlockorder');
      var currentIndex = globalDataInteractionObj.utilities.findIndexByProperty(neighborhoods, 'cat_name', currentNeighborhood);
      var nextIndex = currentIndex + 1;
      if (nextIndex == neighborhoods.length) {
        nextIndex = 0;
      }
      var neighborhoodTagName = neighborhoods[nextIndex].cat_name;
      globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData(neighborhoodTagName);

      // Start new pin drop logic

      // var theID = $('#jqvmap1_' + currentNeighborhood.toLowerCase().replace(' ', ''));
      // var thePin = $('#drop-pin');

      // thePin.css({
      //     top: theID.offset().top,
      //     right: theID.offset().left
      // }).show();
      // var coords = PinDropper.getCoordinates(currentNeighborhood);
      // //console.log(coords);
      // PinDropper.placePin(coords);

    },
    displayCurrentData: function () {

      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var currentMap = globalDataInteractionObj.mapInteractionHandler.currentMap;
      var currentArea = globalDataInteractionObj.mapInteractionHandler.currentArea;
      var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.currentNeighborhood;

      //console.log('currentMap:' + currentMap);
      //console.log('currentArea:' + currentArea);
      //console.log('currentNeighborhood:' + currentNeighborhood);

      // Loading becase have to set currentMapObjectId
      globalDataInteractionObj.mapInteractionHandler.displayMapData(currentMap);

      // Loading becase have to set currentAreaObjectId
      if (typeof(currentArea) != 'undefined' && currentArea != null) {
        globalDataInteractionObj.mapInteractionHandler.displayAreaData(currentArea);
      }

      // Loading becase have to set currentNeighborhoodObjectId
      if (typeof(currentNeighborhood) != 'undefined' && currentNeighborhood != null) {
        globalDataInteractionObj.mapInteractionHandler.displayNeighborhoodData(currentNeighborhood);
      }

      globalDataInteractionObj.mapInteractionHandler.setLegends();
      globalDataInteractionObj.mapInteractionHandler.loadMap();

      if (typeof(currentNeighborhood) != 'undefined' && currentNeighborhood != null) {
        var neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', currentNeighborhood, 'NEIGHBORHOOD');
        //console.log(neighborhoodInfo);
        globalDataInteractionObj.mapInteractionHandler.doEventChanges('autoHash', neighborhoodInfo.parent_url_title, neighborhoodInfo.parent_name);
        globalDataInteractionObj.mapInteractionHandler.setObjectPin();
      }
      else if (typeof(currentArea) != 'undefined' && currentArea != null) {
        var areaInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_name', currentArea, 'AREA');
        globalDataInteractionObj.mapInteractionHandler.doEventChanges('autoHash', areaInfo.cat_url_title, areaInfo.cat_name);
      }
    },
    doEventChanges: function (eventType, code, region) {
      if (eventType == 'regionMouseOut') {
        var selectedColors = globalDataInteractionObj.mapInteractionHandler.getSelectedColors();
        jQuery('#vmap').vectorMap('set', 'colors', selectedColors);
      } else {
        var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
        var areaInfo = null;

        //console.log('eventType '+eventType);
        if (eventType == 'autoHash') {
          areaInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_url_title', code, 'AREA');
          globalDataInteractionObj.mapInteractionHandler.currentArea = areaInfo.cat_name;

        } else if (globalDataInteractionObj.mapInteractionHandler.currentMap == 'EU' && eventType == 'regionClick') {
          areaInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_url_title', code, 'AREA');
          //console.log('doEventChanges regionClick', areaInfo);
          globalDataInteractionObj.mapInteractionHandler.currentArea = areaInfo.cat_name;
        } else {
          var neighborhoodInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_url_title', code, 'NEIGHBORHOOD');
          //console.log(neighborhoodInfo);
          if (typeof(neighborhoodInfo) != 'undefined' && neighborhoodInfo != null) {
            areaInfo = globalDataInteractionObj.utilities.findOneByProperty(currentMapInfo, 'cat_url_title', neighborhoodInfo.parent_url_title, 'AREA');
          }
        }

        //console.log(areaInfo);
        if (typeof(areaInfo) != 'undefined' && areaInfo != null) {
          var neighborhoods = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_url_title', areaInfo.cat_url_title);
          //console.log(neighborhoods);
          var selectedNeighborhoods = [];
          $.each(neighborhoods, function (nkey, nvalue) {
            selectedNeighborhoods.push(nvalue.cat_url_title);
          });

          //console.log(selectedNeighborhoods);
          //console.log('eventType - ' + eventType);
          if (eventType == 'autoHash') {
            globalDataInteractionObj.mapInteractionHandler.displayLegend(areaInfo.cat_url_title);
            globalDataInteractionObj.mapInteractionHandler.setRegionBorderColors(eventType, areaInfo.cat_url_title);
            globalDataInteractionObj.mapInteractionHandler.setRegionColors(eventType, areaInfo.cat_url_title, selectedNeighborhoods, currentMapInfo);
          } else if (eventType == 'regionClick') {
            globalDataInteractionObj.mapInteractionHandler.displayLegend(areaInfo.cat_url_title);
            globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = null;
            globalDataInteractionObj.mapInteractionHandler.displayAreaData(areaInfo.cat_name);
            globalDataInteractionObj.mapInteractionHandler.setRegionBorderColors(eventType, areaInfo.cat_url_title);
            globalDataInteractionObj.mapInteractionHandler.setRegionColors(eventType, areaInfo.cat_url_title, selectedNeighborhoods, currentMapInfo);
          } else if (eventType == 'regionMouseOver') {
            //globalDataInteractionObj.mapInteractionHandler.setRegionColors(eventType, areaInfo.cat_url_title, selectedNeighborhoods, neighborhoods);
          }
        }
      }
    },
    displayAdditionalContent: function (mapObject) {
      var additionalContent = globalDataInteractionObj.mapInteractionHandler.getAdditionalContent();

      if (additionalContent) {
        $.each(additionalContent, function (key, contentObj) {
          var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          text.setAttribute('transform', contentObj.transform);

          $.each(contentObj.content, function (cindex, cvalue) {
            var node = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
            node.setAttribute('x', cvalue.x);
            node.setAttribute('y', cvalue.y);
            node.setAttribute('class', cvalue.class);
            node.textContent = cvalue.text;
            if (contentObj.hasOwnProperty("region")) {
              node.setAttribute('id', mapObject.getCountryId(contentObj.region) + '_text' + cindex);
              node.setAttribute('color', 'currentColor');
              node.onclick = function () {
                var temp = $(this).attr('id').split("_");
                temp.pop();
                temp = temp.join("_");
                $("#" + temp).trigger("click");
                //console.log("clicked " + temp);
              };
            }

            text.appendChild(node);
          });

          jQuery('#vmap svg g').append(text);
        });
      }
    },
    getMapInfo: function () {
      return globalDataInteractionObj.userData.userMapHandler.mapInfo;
    },
    getElanMapObjectId: function () {
      return globalDataInteractionObj.userData.userMapHandler.elanMapObjectId;
    },
    setElanMapObjectId: function () {

      globalDataInteractionObj.userData.userMapHandler.elanMapObjectId = 0;
      var mapTagName = globalDataInteractionObj.userData.userMapHandler.elanMapObjectIdTag;
      var mapObject = globalDataInteractionObj.utilities.findJourneyMap(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, mapTagName);
      if (typeof(mapObject) != 'undefined' && mapObject != null) {
        globalDataInteractionObj.userData.userMapHandler.elanMapObjectId = mapObject.OBJECT_ID;
      }
    },
    getElanMapUnlockObjectId: function () {
      return globalDataInteractionObj.userData.userMapHandler.elanMapUnlockObjectId;
    },
    setElanMapUnlockObjectId: function () {
      globalDataInteractionObj.userData.userMapHandler.elanMapUnlockObjectId = 0;
      var neighborhoodTagName = globalDataInteractionObj.userData.userMapHandler.elanMapUnlockObjectIdTag;
      var neighborhoodObject = globalDataInteractionObj.utilities.findMapNeighborhood(globalDataInteractionObj.mapInteractionHandler.mapLearningObjects, neighborhoodTagName);
      if (typeof(neighborhoodObject) != 'undefined' && neighborhoodObject != null) {
        globalDataInteractionObj.userData.userMapHandler.elanMapUnlockObjectId = neighborhoodObject.OBJECT_ID;
      }
    },
    getOriginalColors: function () {
      return globalDataInteractionObj.userData.userMapHandler.originalColors;
    },
    getDarkColors: function () {
      return globalDataInteractionObj.userData.userMapHandler.darkColors;
    },
    getPins: function () {
      $.each(globalDataInteractionObj.userData.userMapHandler.pins, function (key, value) {
        globalDataInteractionObj.userData.userMapHandler.pins[key] = globalDataInteractionObj.utilities.escapeXml(value);
      });
      return globalDataInteractionObj.userData.userMapHandler.pins;
    },
    setPin: function (key, value) {
      globalDataInteractionObj.userData.userMapHandler.pins[key] = globalDataInteractionObj.utilities.escapeXml(value);
    },
    setLegends: function () {
      var currentMapInfo = globalDataInteractionObj.mapInteractionHandler.getMapInfo();
      var areas = globalDataInteractionObj.utilities.findAllByProperty(currentMapInfo, 'parent_id', '0');
      areas = globalDataInteractionObj.utilities.sortByProperty(areas, 'unlockorder');

      $.each(areas, function (akey, avalue) {
        var liElement = '<li class="' + avalue.cat_url_title + '" data-title="' + globalDataInteractionObj.utilities.lang(avalue.cat_url_title) + '"></li>';
        $('ul.legend-group').append(liElement);
      });
    },
    displayLegend: function (area) {
      $('ul.legend-group li').removeClass('active');
      if (typeof(area) != 'undefined') {
        $('ul.legend-group li.' + area).addClass('active');
      }
    },
    getAdditionalContent: function () {
      return globalDataInteractionObj.userData.userMapHandler.additionalContent;
    },
    getSelectedColors: function () {
      return globalDataInteractionObj.userData.userMapHandler.selectedColors;
    },
    setSelectedColors: function (selectedColors) {
      globalDataInteractionObj.userData.userMapHandler.selectedColors = selectedColors;
    },
    setRegionBorderColors: function (eventType, selectedArea) {
      var newColors = globalDataInteractionObj.userData.userMapHandler.getRegionBorderColors(selectedArea);

      jQuery('#vmap').vectorMap('set', 'colors', newColors);
      // This line used to keep track of latest color and revert back to it after mouse out
      if (eventType == 'regionClick' || eventType == 'autoHash') {
        globalDataInteractionObj.mapInteractionHandler.setSelectedColors(newColors);
      }
    },
    setRegionColors: function (eventType, selectedArea, selectedNeighborhoods, colorToNeighborhoods) {
      //console.log(selectedNeighborhoods);
      //console.log(colorToNeighborhoods);
      var selectedColors = globalDataInteractionObj.mapInteractionHandler.getSelectedColors();
      var originalColors = globalDataInteractionObj.mapInteractionHandler.getOriginalColors();
      var darkColors = globalDataInteractionObj.mapInteractionHandler.getDarkColors();
      var newColors = $.extend({}, selectedColors);

      $.each(colorToNeighborhoods, function (key, value) {
        if (value.parent_id != 0) {
          var name = value.cat_url_title;
          if (jQuery.inArray(name, selectedNeighborhoods) != -1) {
            newColors[name] = originalColors[name];
          } else {
            newColors[name] = darkColors[name];
          }
        }
      });
      jQuery('#vmap').vectorMap('set', 'colors', newColors);
      // This line used to keep track of latest color and revert back to it after mouse out
      if (eventType == 'regionClick' || eventType == 'autoHash') {
        globalDataInteractionObj.mapInteractionHandler.setSelectedColors(newColors);
      }
    },
    loadMap: function () {
      if(!globalDataInteractionObj.isMapLoaded) {
        var mapDiv = jQuery('#vmap');
        // mapDiv.find('svg').empty();

        var is_iPad = globalDataInteractionObj.objectProperties.isIPad;

        if (freshMapObject !== null && !globalDataInteractionObj.isMapLoaded) {
          freshMapObject.reset();
          // console.log("*********************** refresh map ***********************", freshMapObject)
        } else if (globalDataInteractionObj.isMapLoaded) {
          // console.log("*********************** loadMap ALREADY LOADED ***********************", freshMapObject)
        } else {
          // console.log("*********************** loadMap ***********************", freshMapObject)
        }

        freshMapObject = mapDiv.vectorMap({
          map: globalDataInteractionObj.userData.userMapHandler.mapJavaScriptId,
          backgroundColor: '#F3F2EA',
          colors: globalDataInteractionObj.mapInteractionHandler.getOriginalColors(),
          enableZoom: (!is_iPad),
          showTooltip: false,
          showLabels: false,
          regionSelectable: false,
          hoverOpacity: 1,
          series: {
            countries: [{ attribute: 'transform' }],
            regions: [{ attribute: 'fill' }]
          },
          pins: {},
          pinMode: 'content',
          onLoad: function (event, map) {
            if (!globalDataInteractionObj.isMapLoaded) {
              globalDataInteractionObj.isMapLoaded = true;

              //console.log(map);
              globalDataInteractionObj.mapInteractionHandler.currentJQVMapObject = map;
              globalDataInteractionObj.mapInteractionHandler.displayAdditionalContent(map);

              //To set pins as per status
              globalDataInteractionObj.mapInteractionHandler.getNeighborhoodObjectStatus();

              // This is to display the destination and destination image on the profile page.
              globalDataInteractionObj.userData.displayTargetArrival();
              globalDataInteractionObj.userData.displayNextDestination();
              globalDataInteractionObj.userData.displayPassportStamps();
              // This is to display the journey accordion on the profile page.
              globalDataInteractionObj.userData.displayJourneyAccordion();
              globalDataInteractionObj.userData.myProfileTabEventHandler();
              globalDataInteractionObj.userData.getJourneyVideo();
            }
          },
          onRegionOver: function (event, code, region) {
            //console.log('onRegionOver - ' + event + '-' + code + ' - ' + region);
            //console.log(event);
            //globalDataInteractionObj.mapInteractionHandler.doEventChanges(event.type, code, region);
          },
          onRegionOut: function (event, code, region) {
            //console.log('onRegionOut - ' + event + '-' + code + ' - ' + region);
            //console.log(event);
            //console.log(selectedColors);
            //globalDataInteractionObj.mapInteractionHandler.doEventChanges(event.type, code, region);
          },
          onRegionClick: function (event, code, region) {
            event.preventDefault();
            //console.log('onRegionClick - ' + event.type + '-' + code + ' - ' + region);
            globalDataInteractionObj.mapInteractionHandler.doEventChanges(event.type, code, region);
          },
          onResize: function (event, width, height) {
            setTimeout(function () {
              //alert("width:" + width);
              //alert("height:" + height);
              globalDataInteractionObj.mapInteractionHandler.setObjectPin();
            }, 200);
          }
        });
      }
    },
    wireMapButtons: function() {
      if(!globalDataInteractionObj.buttonsWired) {
        $('body').on('click', '.nyc', function (e) {
          e.preventDefault();
          if (globalDataInteractionObj.userData.mlRankMap() != 'nyc') {
            location.hash = "journey";
            globalDataInteractionObj.isMapLoaded = false;
            globalDataInteractionObj.ajaxUtilities.ajaxLauncher('mapsetup', 'mlapprentice');
          }
        });

        $('body').on('click', '.europe', function (e) {
          e.preventDefault();
          if (globalDataInteractionObj.userData.mlRankMap() != 'eu') {
            location.hash = "journey|europe";
            globalDataInteractionObj.isMapLoaded = false;
            globalDataInteractionObj.ajaxUtilities.ajaxLauncher('mapsetup', 'eu');
          }
        });
        globalDataInteractionObj.buttonsWired = true;
      }
    },
    resetMap: function () {
      location.hash = 'journey'; //only change it for first load
      globalDataInteractionObj.isMapLoaded = false;
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher('mapsetup', 'mlapprentice');
      globalDataInteractionObj.mapInteractionHandler.wireMapButtons();
    },
    resetMapThen: function () {
      var mapObject = globalDataInteractionObj.mapInteractionHandler.currentJQVMapObject;
      if(typeof(mapObject.reset) == 'function'){
        mapObject.reset();
      }
      mapObject.zoomCurStep = 1;

      globalDataInteractionObj.mapInteractionHandler.currentArea = null;
      globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = null;
      globalDataInteractionObj.mapInteractionHandler.displayLegend();

      // var currentMap = globalDataInteractionObj.mapInteractionHandler.currentMap;
      // globalDataInteractionObj.mapInteractionHandler.displayMapData(currentMap);
      //console.log('currentMap: ' + currentMap);

      var originalColors = globalDataInteractionObj.mapInteractionHandler.getOriginalColors();
      globalDataInteractionObj.mapInteractionHandler.setSelectedColors(originalColors);
      //jQuery('#vmap').vectorMap('set', 'colors', originalColors);

      //var currentNeighborhood = globalDataInteractionObj.mapInteractionHandler.getNeighborhoodObjectStatus();
      //console.log('currentNeighborhood: ' + currentNeighborhood);
    },
    mapDataInit: function () {
      globalDataInteractionObj.mapInteractionHandler.fetchMapLearningObjects();
    },
    mapInit: function () {
      //console.log("mapInit");
      globalDataInteractionObj.mapInteractionHandler.currentMap = globalDataInteractionObj.userData.userMapHandler.elanMapTag;

      //Commented out because on refresh user goes back to home page
      /*
       if(location.hash != '' && location.hash != '#') {
       var page = location.hash;
       page = page.replace('#', '');
       page = page.split("|");

       //console.log('page length:' + page.length);
       if( typeof(page[0]) != 'undefined' && page[0] == 'journey') {
       //console.log('page:' + page[0]);

       if( typeof(page[1]) != 'undefined' && page[1] != '' ) {
       globalDataInteractionObj.mapInteractionHandler.currentArea = page[1];
       }
       if( typeof(page[2]) != 'undefined' && page[2] != '' ) {
       globalDataInteractionObj.mapInteractionHandler.currentNeighborhood = page[2];
       }
       }
       }
       */
      globalDataInteractionObj.mapInteractionHandler.loadSlideOutPanel();
    }
  },
  //initialization sequence for the whole object. Call this method on a window onload or a domready event
   init: function () {
    if(member_profile_data.elan_user_id != '') {
      //this.utilities.getCurrentMonth();
      this.userData.displayMeterCompletions();
      this.userData.userInit();
      //stopInfiniteLoop += 1;

      //}else{
      // //console.log("init", stopInfiniteLoop)
    }
  },
  //this should be used to grab user and course data on asynchronous view load events
  update: function () {
    this.userData.userUpdate();
  }
};
//Change link colors on explore modal and swap checkbox image based on clicks
var idleTime = 0;
$(document).ready(function () {
  $('.language-modal').hide();
  // $('#off-canvas-menu').hide();

  globalDataInteractionObj.utilities.setAllowedLanguages('language-model-list');


  $('.translator-icon').click(function () {
    //$('.language-modal').width($('body').width() * .95);
    $('.language-modal').show();

    $('.language-modal').unbind().click(function (e) {
      if ($(e.target).hasClass('icon-close-btn') || !$(e.target).parents('.language-modal').length) {
        $('.language-modal').hide();
      }
    });

    $('.change-language').unbind().click(function (e) {
      var language_id = $(this).attr('attr-lang-id');
      var language_abbr = $(this).attr('attr-lang-abbr');

      $('#change-lang-hidden-input').val(language_abbr);
      var parameters = {
        "type": "updateuserlanguageid",
        "language_id": language_id
      };
      $.ajax(globalDataInteractionObj.ajaxUtilities.ajaxSwitch('GET', parameters, 'api')).done(function (response) {
        $('form.change-language-form').submit();
      });
    });

  });

  g2 = (typeof(JustGage) != 'undefined') ? new JustGage({
    id: 'g2',
    min: 0,
    max: 22,
    relativeGaugeSize: true,
    gaugeColor: '#50B586',
    hideMinMax: true,
    pointer: true,
    hideValue: true,
    pointerOptions: {
      toplength: -30,
      bottomlength: 50,
      bottomwidth: 6,
      color: '#50B586',
      stroke_linecap: 'round'
    },
    customSectors: [{
      color: '#C6F1DD',
      lo: 0,
      hi: 4
    }, {
      color: '#99E0BF',
      lo: 4,
      hi: 12
    }
      , {
        color: '#77CFA6',
        lo: 12,
        hi: 16
      }
      , {
        color: '#50B586',
        lo: 16,
        hi: 22
      }],
    levelColorsGradient: false,
    gaugeWidthScale: 0.8,
    counter: false,
    parseTime:false
  }) : {};
  //Increment the idle time counter every minute.
  if (idleInterval) {
    clearInterval(idleInterval);
  }
  idleInterval = setInterval(timerIncrement, 60000); // 1 minute

  //Zero the idle timer on mouse movement.
  this.addEventListener("mousemove", resetTimer, false);
  this.addEventListener("mousedown", resetTimer, false);
  this.addEventListener("keypress", resetTimer, false);
  this.addEventListener("DOMMouseScroll", resetTimer, false);
  this.addEventListener("mousewheel", resetTimer, false);
  this.addEventListener("touchmove", resetTimer, false);
  this.addEventListener("MSPointerMove", resetTimer, false);

});

function resetTimer(e) {
  idleTime = 0;
}
function timerIncrement() {
  idleTime = idleTime + 1;
  if (idleTime > 59) { // 1 hour
    //console.log('timerIncrement()', idleTime);
    // window.location.replace(globalDataInteractionObj.objectProperties.appLogout);
  }
}


$(function () {
  globalDataInteractionObj.init();

  // Added By Dave

  (function () {

    // Clear Hashes On Load
    window.location.hash = '';

    var body = $('body');
    var userMenu = $('#off-canvas-menu');
    var menuDimmer = $('#menu-dimmer');
    //var menuTransition = new TimelineLite();
    var menuClosed = true;

    (function pageRoutes() {
      var navLinks = $('a.coach-nav-link');
      var pageTransition = new TimelineLite();
      var allSections = $('.full-page-section');
      var preloader = $('#preloader');

      // Place non active page sections off canvas on load
      TweenLite.to(allSections.not('.activestate'), .01, { x: 700 });
      allSections.not('.activestate').hide();

      navLinks.on('click touchstart', function (e) {
        e.preventDefault();

        var $this = $(this).parent('li');

        //var destinationPath = this.pathname.replace('/', '');
        var destinationPath = this.pathname.split('/').slice(-1)[0];
        var destination = $('#' + destinationPath);

        //if ($this.hasClass('activestate')) {
          if (destinationPath == 'mapJourney') {
            if( $('body').attr('data-page') == "mapJourney" ) {
              console.log('+++++++++++ click mapJourney from mapJourney' );
              if(globalDataInteractionObj.mapInteractionHandler.currentMap == ('' || 'ML APPRENTICE')) {
                window.location.hash = '';
                globalDataInteractionObj.mapInteractionHandler.resetMap();
              } else {
                var mapTag = globalDataInteractionObj.mapInteractionHandler.currentMap.replace(' ','').toLowerCase();
                location.hash = (mapTag == 'mlapprentice') ? "journey" : "journey|"+mapTag;
                globalDataInteractionObj.isMapLoaded = false;
                globalDataInteractionObj.ajaxUtilities.ajaxLauncher('mapsetup', mapTag);
              }
            } else {
              console.log('+++++++++++ click mapJourney currentMap' + globalDataInteractionObj.mapInteractionHandler.currentMap);
            }
          }
          //return;
        //}

        // Remove Monthly Focus Page Modal If Open Before Page Transition
        $('.fade-in-trigger').removeClass('fade-in-trigger');

        if(destinationPath != $('body').attr('data-page') ) { //if on the same page
          var currentPage = $('.full-page-section.activestate');

          pageTransition
            .to(currentPage, .65, { x: -700, autoAlpha: 0, ease: 'Expo.easeIn' })
            .to(currentPage, .01, {
              x: 2700, onComplete: function () {
                currentPage.hide();
                body.attr('data-page', destinationPath);
                $(window).scrollTop(0);
                navLinks.parents('li').add(allSections).removeClass('activestate');
                $this.add(destination).addClass('activestate');
                destination.show(1, function () {
                  $this.css('pointer-events', 'auto');
                  $(window).trigger('resize');
                });

              }
            })
            .to(destination, .65, {
              x: 0, autoAlpha: 1, ease: 'Expo.easeOut', onComplete: function () {
                //console.log('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> transition to ' + destination.attr('id'));
                if (destination.attr('id') == 'mapJourney') {
                  globalDataInteractionObj.isMapLoaded = false;
                  // globalDataInteractionObj.mapInteractionHandler.resetMap();
                  if (globalDataInteractionObj.mapInteractionHandler.currentMap == '' || globalDataInteractionObj.mapInteractionHandler.currentMap == 'ML APPRENTICE') {
                    window.location.hash = '';
                    globalDataInteractionObj.mapInteractionHandler.resetMap();
                  } else if (globalDataInteractionObj.mapInteractionHandler.currentMap) {
                    var mapTag = globalDataInteractionObj.mapInteractionHandler.currentMap.replace(' ', '').toLowerCase();
                    location.hash = (mapTag == 'mlapprentice') ? "journey" : "journey|" + mapTag;
                    globalDataInteractionObj.isMapLoaded = false;
                    globalDataInteractionObj.ajaxUtilities.ajaxLauncher('mapsetup', mapTag);
                  } else {
                    globalDataInteractionObj.mapInteractionHandler.resetMap();
                  }
                  $(window).trigger('resize');

                } else if (destination.attr('id') == 'myProfileBlock') {
                  //console.log('Update data on profile page');
                  globalDataInteractionObj.ajaxUtilities.ajaxLauncher('profilesetup');
                }
              }
            });
        }
      });
    })();

    function toggleMenu(state, timing) {
      menuClosed = !menuClosed;
      (menuClosed) ? body.removeClass('no-scroll') : body.addClass('no-scroll');
      userMenu.toggle();
      $('#menu-dimmer').toggle();
    }

    (function slideMenu() {
      userMenu.hide();

      $('.menu-burger').on('click', function () {
          toggleMenu();
      });

      menuDimmer.add('#off-canvas-menu .icon-menu-cross').on('click', function () {
        toggleMenu();
      });

      $(window).on('resize.userMenu', function () {
        userMenu.hide();
        $('#menu-dimmer').hide();
      });

    })();

    (function videoPlayer() {

      $('body').on('click touchstart', '.play-video', function (e) {
        if (!$(this).hasClass('disabled')) {
          e.preventDefault();
          var record_id = $(this).attr('attr-record-id');
          var parent_object_id = $(this).attr('attr-parent');
          var object_id = $(this).attr('id');
          var step_num = 1;
          $('div.pre-load-modal').fadeIn(300);
          globalDataInteractionObj.ajaxUtilities.ajaxLauncher('getcoursevideo', record_id, parent_object_id, object_id, step_num);
        }
      });
      $('body').on('tap', function () {
        if (player.userActive() === true) {
          player.userActive(false);
        } else {
          player.userActive(true);
        }
      });
    })();

    (function pdfModal(){
      var country_code;
      var currentUserName = globalDataInteractionObj.utilities.getCookie("exp_elan_username");
      //open pdf in modal window
      $('body').on('click', 'ul li a.Link', function(e){
        e.preventDefault();
        var modal = $('#pdf-modal');
        var preloader = $('#pdf-preloader');
        var theSrc;
        var windowHandle;
              var parent_id = $(this).attr('attr-parent');
              var object_id = $(this).attr('id');
              var theHref = this.href;
                  //$.getJSON('https://freegeoip.net/json/?callback=?', function (location, textStatus, jqXHR) {
				  var country_code = globalDataInteractionObj.utilities.getCountryCode(); 
				  var location_ip = globalDataInteractionObj.utilities.getLocationIP();
				//var country_code = location.country_code;
				  var currentUserName = globalDataInteractionObj.utilities.getCookie("exp_elan_username");
				  if(!$(this).hasClass('disabled')) {
					  theHref = theHref.replace('http:/','https:/');
						if(country_code == 'CN' || currentUserName == 'sumanafazalretail' || currentUserName == 'joonretail1' || locationIP == '116.50.57.180'){
						 // //console.log('Country - ' + country_code + 'IP address: ' + location_ip);
						 
						 windowHandle = window.open('','pdfTab', 'status=1');
						  if(typeof(windowHandle) == 'undefined' || windowHandle.closed){
							//create new
							windowHandle.document.location.href = theHref; 
							windowHandle.focus();
							} else {
							windowHandle.close();
							//it exists, load new content (if necs.)
							windowHandle = window.open('','pdfTab', 'status=1');
							windowHandle.location.href = theHref;
							//give it focus (in case it got burried)
							windowHandle.focus();
							}

						 globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updatecreateuserrecord', object_id, parent_id,'Complete');  
						 
						 } else if (theHref.indexOf('stylegame.coach.com') == -1 && country_code != 'CN') {
						  // Replace this with BIW function	  
							  //console.log('Country - ' + country_code);
							  // if(theHref.indexOf('stylegame.coach.com') == -1) {
							  theSrc = (window.innerWidth < 1025) ? 'https://drive.google.com/viewerng/viewer?embedded=true&url=' + theHref : theHref; 
							  modal.find('iframe').attr('src', theSrc);
							  preloader.show();
							  modal.fadeIn(300);
							  $('body').addClass('modal-open'); 
						  } else {
							  theSrc = theHref;  
							  modal.find('iframe').attr('src', theSrc);
							  preloader.show();
							  modal.fadeIn(300);
							  $('body').addClass('modal-open');
						  }  
						globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updatecreateuserrecord', object_id, parent_id,'Complete');
						monthly_focus.child = true;
					}
				//});

        $('body').on('click touchstart', '.course-detail .icon-close-btn', function(){
          modal.fadeOut(300, function(){
            $('div.course-detail').html('');
            monthly_focus.child = false;
            $('body').removeClass('modal-open');
          });
        });
		
		$(window).on('unload', function(){
				var windowHandle = window.open('','pdfTab', 'status=1');
				windowHandle.close();
			});

        modal.add('close-button').on('click touchstart', function(){
          modal.fadeOut(300, function(){
            monthly_focus.child = false;
            modal.find('iframe').attr('src', '');
            $('body').removeClass('modal-open');
          });
        });

        $('iframe').on('load', function(){
          preloader.fadeOut(100);
        });

      });

      //open and fill quiz in modal window
      $('body').on('click touchstart', 'ul li a.Quiz', function(e){
        // //console.log("quiz clicked");
        e.preventDefault();
        if(!$(this).hasClass('disabled')) {
          $('div.pre-load-modal').fadeIn(300);
          var modal = $('div.quiz-container');
          // modal.width($('.test-size').width());
          var parent_id = $(this).attr('attr-parent');
          var object_id = $(this).attr('id');
          var count = 1;
          var answers = [];
          var pass_percent = $(this).attr('attr-quiz-percent');
          //console.log('pass_percent: ' + pass_percent);
          //console.log(answers);
          monthly_focus.child = true;

          var record_id = $("#quiz_record_id").val();

          // //console.log("Modal-"+modal);
          globalDataInteractionObj.ajaxUtilities.fillQuizQuestions(object_id, parent_id);
          modal.fadeIn(500);
        }
        $('body').on('click touchstart', '.quiz-container .icon-close-btn', function(){
          modal.fadeOut(300, function(){
            $('div.quiz-container').html('');
            monthly_focus.child = false;
            $('body').removeClass('modal-open');
          });
        });

        //this function will detect user choice in quiz and reply back with incorrect or correct and then disable other choice.
        // **** ADDED 3-22-2017
        $('body').on('click touchstart', '.quiz-container .question .user-answer', function(){
          if($("input[name='"+count+"']").is(':checked')) {
            var input_value = $("input[name='"+count+"']:checked").val();
            if ( $( "#selected-ans-correct" ).is( ".correct-ans" ) ) {
              $('.correct-ans-'+count+'-'+input_value+' .correct-ans').show();
            }
            if ( $( "#selected-ans-incorrect" ).is( ".incorrect-ans" ) ) {
              $('.correct-ans-'+count+'-'+input_value+' .incorrect-ans').show();
            }
            $("input[name='"+count+"']").attr('disabled', true);
            //console.log($('.correct-ans-'+count+'-'+input_value));
          }
        });

        var quizflag = false;
        $('body').on('click touchstart', '.quiz-container .quiz-questions .next', function(e){
          e.preventDefault;
          //Hide all of the error messages
          if (!quizflag) {
            $('.quiz-container .errormsg').hide();

            //check to see if they selected an answer
            if($("input[name='"+count+"']").is(':checked')) {
              answers.push($("input[name='"+count+"']:checked").val());
              //Check to see if this was the last question
              //console.log(count);
              if(count < quiz.data.DATA.length) {
                setTimeout(function(){
                  $('.quiz-container .question'+count).hide();
                  count ++;
                  $('.quiz-container .question'+count).show();
                }, 250);
              } else {
                quiz.answers = answers;
                answers = [];
                //console.log('pass_percent: ' + pass_percent);
                gradeQuiz(pass_percent, object_id, parent_id);
              }
            } else {
              $('.quiz-container .errormsg').show();
            }
            quizflag = true;
            setTimeout(function(){ quizflag = false;}, 450);
          }

          //console.log(answers);
        });



      });

      //open scorm in new window
      $('body').on('click touchstart', 'ul li a.SCORM', function(e) {
        e.preventDefault();
        if( !$(this).hasClass('disabled') ) {
          var parent_id = $(this).attr('attr-parent');
          var object_id = $(this).attr('id');
          globalDataInteractionObj.utilities.launchMonthlyFocusCourse(object_id, parent_id);
        }
        globalDataInteractionObj.ajaxUtilities.removeDisableClassForNextChild(object_id, parent_id);
      });

    })();

    function reviewAnswer() {
      //console.log(quiz.answers);
      $('.quiz-container .quiz-questions').hide();
      $('.quiz-container .quiz-results').hide();
      var current_question_no = $('.quiz-container .quiz-review #current_question_no').val();
      $('.quiz-container .quiz-review .question').hide();
      $('.quiz-container .quiz-review .question' + current_question_no).show();
      $('span.user-answer').html(quiz.answers[current_question_no - 1]);
      if (current_question_no == quiz.data.DATA.length) {
        $('.quiz-container .quiz-review button.next').hide();
        $('.quiz-container .quiz-review .review-end').show();
      }

      $('.quiz-container .quiz-review').show();
    }

    function gradeQuiz(pass_percent, object_id, parent_id) {
      var correct = 0;
      var question_count = 1;
      var record_id = $("#quiz_record_id").val();
      var pass = "Failed";
      //console.log(quiz.answers);
      quiz.answers.forEach(function (answer) {
        //console.log(answer);
        if (quiz.data.DATA[question_count - 1]['ANSWERS'][answer - 1]['CORRECT'] == 1) {
          correct++;
        }
        question_count++;

        globalDataInteractionObj.ajaxUtilities.ajaxLauncher('submitquizanswers', record_id, answer, question_count - 1);
      });
      question_count--;

      //var user_percent = (correct / question_count) * 100;
      var user_percent = Math.round((correct / question_count).toFixed() * 100);
      //console.log('correct: ' + correct);
      //console.log('question_count: ' + question_count);
      //console.log('pass_percent: ' + pass_percent);
      //console.log('user_percent: ' + user_percent);

      if (user_percent >= pass_percent) {
        pass = "Passed";

        var quiz_results_html = globalDataInteractionObj.utilities.lang('congratulations') + "<p>" + globalDataInteractionObj.utilities.lang('passed_assessment') + "</p>";

        quiz_results_html += "<br/><p>" + globalDataInteractionObj.utilities.lang('review_answer_text') + "</p>";
        quiz_results_html += "<button id=\"reviewanswers\" class=\"btn btn-default\">" + globalDataInteractionObj.utilities.lang('review_answers') + "</button>";

        $('.quiz-container .quiz-results').html(quiz_results_html);

        $('.quiz-container .quiz-results #reviewanswers').on('click', function (e) {
          e.preventDefault;
          $('.quiz-review #current_question_no').val(1);
          reviewAnswer();
        });

        $('.quiz-container .quiz-review button.next').on('click', function (e) {
          e.stopImmediatePropagation();
          e.preventDefault;
          var current_question_no = $('.quiz-review #current_question_no').val();
          current_question_no = parseInt(current_question_no) + 1;
          $('.quiz-review #current_question_no').val(current_question_no);
          reviewAnswer();
        });

        $('#svg-' + parent_id).html(user_percent + '%');
        $('.svg-' + parent_id).show();

        globalDataInteractionObj.ajaxUtilities.removeDisableClassForNextChild(object_id, parent_id);
        var passed = 0;
        for (var row_key in globalDataInteractionObj.objectProperties.rawAjaxCourseData) {
          for (var object_key in globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key]) {
            if (globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['course_id'] == parent_id) {
              globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['course_percent'] = user_percent + '%';
            }
            if (globalDataInteractionObj.objectProperties.rawAjaxCourseData[row_key][object_key]['course_percent'] != '') {
              passed++;
            }
          }
        }
        globalDataInteractionObj.objectProperties.rawAjaxCourseData['passed_assessments'] = passed;
        var percent_done = Math.round(passed / globalDataInteractionObj.objectProperties.rawAjaxCourseData['total_assessments'] * 100);
        $('.data-course-completion').text(percent_done + '%');

      } else {
        $('.quiz-container .quiz-results').html(globalDataInteractionObj.utilities.lang('failed_assessment') + ".  " + globalDataInteractionObj.utilities.lang('min_passing_score') + " " + pass_percent + "%.  " + globalDataInteractionObj.utilities.lang('review_and_retake') + ".");
      }
      $('.quiz-container .question').hide();
      $('.quiz-container .quiz-results').show();

      //console.log(record_id + ' - ' + pass);
      globalDataInteractionObj.ajaxUtilities.ajaxLauncher('updateuserobjectrecord', record_id, pass, user_percent);
      //console.log(correct+ " Correct out of "+question_count);


    }


  })();

});

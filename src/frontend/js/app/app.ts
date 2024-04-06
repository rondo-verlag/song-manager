/// <reference path="references.ts" />


/**
 * The main RondoApp module.
 *
 * @type {angular.IModule}
 */
module rondo {
  'use strict';

  var VERSION = '4';

  var RondoApp = angular.module('RondoApp', ['ngRoute', 'angularFileUpload']);

  RondoApp.controller('SongAddCtrl', SongAddCtrl);
  RondoApp.controller('SongDetailCtrl', SongDetailCtrl);
  RondoApp.controller('SongListCtrl', SongListCtrl);
  RondoApp.filter("yesno", rondo.filters.yesno);
  RondoApp.filter("encodeURIComponent", rondo.filters.encodeURIComponent);
  RondoApp.directive("status", rondo.directives.status);
  RondoApp.directive("license", rondo.directives.license);
  RondoApp.directive("licensetype", rondo.directives.licensetype);
  RondoApp.directive("yesno", rondo.directives.yesno);

  RondoApp.config(['$routeProvider',
    function($routeProvider: angular.route.IRouteProvider) {
      $routeProvider.
        when('/songs', {
          templateUrl: 'frontend/js/app/view/song-list.html?v=' + VERSION,
          controller: 'SongListCtrl'
        }).
        when('/songs/:songId', {
          templateUrl: 'frontend/js/app/view/song-detail.html?v=' + VERSION,
          controller: 'SongDetailCtrl'
        }).
        when('/add', {
          templateUrl: 'frontend/js/app/view/song-add.html?v=' + VERSION,
          controller: 'SongAddCtrl'
        }).
        otherwise({
          redirectTo: '/songs'
        });
    }
  ]);
}




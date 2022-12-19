/// <reference path="../references.ts" />

module rondo {
  'use strict';

  export interface ISongListScope extends ng.IScope {
    list: Array<ISong>;
    search: String;
    orderBy: String;
    orderReversed: boolean;
    filter2017Active: boolean;
    filter2021Active: boolean;
    filter2024Active: boolean;
    filterAppActive: boolean;
    editSong(int): void;
    releaseFilter(item: ISong): boolean;
  }

  export class SongListCtrl {
    public static $inject = [
      '$scope', '$http', '$location'
    ];

    constructor(
      private $scope: ISongListScope,
      private $http: ng.IHttpService,
      private $location: ng.ILocationService
    ) {
      $scope.list = [];
      $scope.search = "";
      $scope.orderBy = 'title';
      $scope.orderReversed = false;
      $scope.filter2017Active = false;
      $scope.filter2021Active = false;
      $scope.filter2024Active = false;
      $scope.filterAppActive = false;

      $scope.releaseFilter = function(item: ISong) {
        if ($scope.filter2017Active || $scope.filter2021Active || $scope.filter2024Active || $scope.filterAppActive) {
          let show = false;
          if ($scope.filter2017Active && item.releaseBook2017 == 1) {
            show = true
          }
          if ($scope.filter2021Active && item.releaseBook2021 == 1) {
            show = true
          }
          if ($scope.filter2024Active && item.releaseBook2024 == 1) {
            show = true
          }
          if ($scope.filterAppActive && item.releaseApp2024 == 1) {
            show = true
          }
          return show;
        } else {
          return true;
        }
      };

      $http.get("api/songs")
        .success(function(data: Array<ISong>, status, headers, config) {
          $scope.list = data;
        })
        .error(function(data, status, headers, config) {
          console.log("AJAX failed!");
        });

      $scope.editSong = function(id: string){
        $location.path('/songs/'+id);
      }
    }
  }
}

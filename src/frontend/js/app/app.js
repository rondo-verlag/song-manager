/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    'use strict';
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    var filters;
    (function (filters) {
        'use strict';
        function yesno() {
            return function (input) {
                return (input == 1 ? 'Ja' : 'Nein');
            };
        }
        filters.yesno = yesno;
        function encodeURIComponent() {
            return function (input) {
                return window.encodeURIComponent(input);
            };
        }
        filters.encodeURIComponent = encodeURIComponent;
    })(filters = rondo.filters || (rondo.filters = {}));
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    var directives;
    (function (directives) {
        'use strict';
        function license() {
            return {
                templateUrl: 'frontend/js/app/directives/license.html',
                scope: {
                    license: '='
                },
                restrict: 'E'
            };
        }
        directives.license = license;
    })(directives = rondo.directives || (rondo.directives = {}));
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    var directives;
    (function (directives) {
        'use strict';
        function licensetype() {
            return {
                templateUrl: 'frontend/js/app/directives/licensetype.html',
                scope: {
                    license: '='
                },
                restrict: 'E'
            };
        }
        directives.licensetype = licensetype;
    })(directives = rondo.directives || (rondo.directives = {}));
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    var directives;
    (function (directives) {
        'use strict';
        function status() {
            return {
                templateUrl: 'frontend/js/app/directives/status.html',
                scope: {
                    status: '='
                },
                restrict: 'E'
            };
        }
        directives.status = status;
    })(directives = rondo.directives || (rondo.directives = {}));
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    var directives;
    (function (directives) {
        'use strict';
        function yesno() {
            return {
                templateUrl: 'frontend/js/app/directives/yesno.html',
                scope: {
                    state: '='
                },
                restrict: 'E'
            };
        }
        directives.yesno = yesno;
    })(directives = rondo.directives || (rondo.directives = {}));
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    'use strict';
    var SongAddCtrl = /** @class */ (function () {
        function SongAddCtrl($scope, $http) {
            this.$scope = $scope;
            this.$http = $http;
            $scope.song = {
                title: '',
                interpret: ''
            };
            $scope.showLoading = false;
            $scope.add = function () {
                if ($scope.song.title != '') {
                    $scope.showLoading = true;
                    $http.post("api/songs", $scope.song)
                        .success(function (data, status, headers, config) {
                        $scope.showLoading = false;
                        $scope.song.title = '';
                        $scope.song.interpret = '';
                    })
                        .error(function (data, status, headers, config) {
                        console.log("AJAX failed!", data, status);
                    });
                }
            };
        }
        SongAddCtrl.$inject = [
            '$scope', '$http'
        ];
        return SongAddCtrl;
    }());
    rondo.SongAddCtrl = SongAddCtrl;
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    'use strict';
    var SongDetailCtrl = /** @class */ (function () {
        function SongDetailCtrl($scope, $http, $routeParams, $location, $sce, FileUploader, $timeout) {
            this.$scope = $scope;
            this.$http = $http;
            this.$routeParams = $routeParams;
            this.$location = $location;
            this.$sce = $sce;
            this.FileUploader = FileUploader;
            this.$timeout = $timeout;
            var self = this;
            $scope.song = null;
            $scope.showAccords = true;
            $scope.prevSongId = parseInt($routeParams.songId) - 1;
            $scope.nextSongId = parseInt($routeParams.songId) + 1;
            $scope.showSavedIcon = false;
            $scope.langOptions = [
                { id: 'de', value: 'Deutsch' },
                { id: 'ch', value: 'Mundart' },
                { id: 'fr', value: 'Französisch' },
                { id: 'it', value: 'Italienisch' },
                { id: 'en', value: 'Englisch' },
                { id: 'other', value: 'Andere' },
            ];
            $scope.youtubeVideoId = null;
            this.loadData();
            $scope.uploader = new FileUploader({
                url: 'api/songs/' + $routeParams.songId + '/musicxmlfiles'
            });
            $scope.uploader.onCompleteItem = function (item) {
                $scope.uploader.clearQueue();
                self.loadData();
            };
            $scope.uploadFile = function (files, type) {
                if (files.length > 0) {
                    var fd = new FormData();
                    //Take the first selected file
                    fd.append("file", files[0]);
                    $http.post("api/songs/" + $routeParams.songId + "/" + type, fd, {
                        withCredentials: true,
                        headers: { 'Content-Type': undefined },
                        transformRequest: angular.identity
                    }).success(function () {
                        self.loadData();
                    }).error(function () {
                        alert('Datei konnte nicht hochgeladen werden.');
                        self.loadData();
                    });
                }
            };
            $scope.uploadGenericFile = function (files, type) {
                if (files.length > 0) {
                    var fd = new FormData();
                    //Take the first selected file
                    fd.append("file", files[0]);
                    $http.post("api/files?songId=" + $routeParams.songId + "&type=" + type, fd, {
                        withCredentials: true,
                        headers: { 'Content-Type': undefined },
                        transformRequest: angular.identity
                    }).success(function () {
                        self.loadData();
                    }).error(function () {
                        alert('Datei konnte nicht hochgeladen werden.');
                        self.loadData();
                    });
                }
            };
            $scope.deleteFile = function (file) {
                if (confirm('Datei ' + file.name + ' wirklich löschen?')) {
                    $http["delete"]("api/files/" + file.id)
                        .success(function (data, status, headers, config) {
                        self.loadData();
                    })
                        .error(function (data, status, headers, config) {
                        alert("Datei konnte nicht gelöscht werden.");
                        self.loadData();
                    });
                }
            };
            $scope.save = function () {
                //console.log($scope.song);
                $http.put("api/songs/" + $routeParams.songId, $scope.song)
                    .success(function (data, status, headers, config) {
                    self.loadData();
                    $scope.showSavedIcon = true;
                    $timeout(function () {
                        $scope.showSavedIcon = false;
                    }, 3000);
                })
                    .error(function (data, status, headers, config) {
                    console.log("AJAX failed!", data, status);
                    self.loadData();
                });
            };
            this.$scope.showList = function () {
                $location.path('/songs');
            };
        }
        SongDetailCtrl.prototype.loadData = function () {
            var self = this;
            this.$http.get("api/songs/" + this.$routeParams.songId)
                .success(function (data, status, headers, config) {
                self.$scope.song = data;
                var ytId = null;
                if (data.youtubeLink) {
                    var parts = data.youtubeLink.split('=');
                    if (parts.length === 2) {
                        ytId = parts[1];
                    }
                }
                self.$scope.youtubeVideoId = ytId;
            })
                .error(function (data, status, headers, config) {
                console.log("AJAX failed!");
            });
            this.$http.get("api/songs/" + this.$routeParams.songId + "/html")
                .success(function (data, status, headers, config) {
                self.$scope.preview = self.$sce.trustAsHtml(data);
            })
                .error(function (data, status, headers, config) {
                console.log("AJAX failed!");
            });
        };
        SongDetailCtrl.$inject = [
            '$scope', '$http', '$routeParams', '$location', '$sce', 'FileUploader', '$timeout'
        ];
        return SongDetailCtrl;
    }());
    rondo.SongDetailCtrl = SongDetailCtrl;
})(rondo || (rondo = {}));
/// <reference path="../references.ts" />
var rondo;
(function (rondo) {
    'use strict';
    var SongListCtrl = /** @class */ (function () {
        function SongListCtrl($scope, $http, $location) {
            this.$scope = $scope;
            this.$http = $http;
            this.$location = $location;
            $scope.list = [];
            $scope.search = "";
            $scope.orderBy = 'title';
            $scope.orderReversed = false;
            $scope.filter2017Active = false;
            $scope.filter2021Active = false;
            $scope.filter2024Active = false;
            $scope.filterAppActive = false;
            $scope.releaseFilter = function (item) {
                if ($scope.filter2017Active || $scope.filter2021Active || $scope.filter2024Active || $scope.filterAppActive) {
                    var show = false;
                    if ($scope.filter2017Active && item.releaseBook2017 == 1) {
                        show = true;
                    }
                    if ($scope.filter2021Active && item.releaseBook2021 == 1) {
                        show = true;
                    }
                    if ($scope.filter2024Active && item.releaseBook2024 == 1) {
                        show = true;
                    }
                    if ($scope.filterAppActive && item.releaseApp2024 == 1) {
                        show = true;
                    }
                    return show;
                }
                else {
                    return true;
                }
            };
            $http.get("api/songs")
                .success(function (data, status, headers, config) {
                $scope.list = data;
            })
                .error(function (data, status, headers, config) {
                console.log("AJAX failed!");
            });
            $scope.editSong = function (id) {
                $location.path('/songs/' + id);
            };
        }
        SongListCtrl.$inject = [
            '$scope', '$http', '$location'
        ];
        return SongListCtrl;
    }());
    rondo.SongListCtrl = SongListCtrl;
})(rondo || (rondo = {}));
/// <reference path="../typings/tsd.d.ts" />
/// <reference path="models/Song.ts" />
/// <reference path="filters/filters.ts"/>
/// <reference path="directives/license.ts"/>
/// <reference path="directives/licensetype.ts"/>
/// <reference path="directives/status.ts"/>
/// <reference path="directives/yesno.ts"/>
/// <reference path="controller/SongAddController.ts"/>
/// <reference path="controller/SongDetailController.ts"/>
/// <reference path="controller/SongListController.ts"/>
/// <reference path="app.ts"/>
/// <reference path="references.ts" />
/**
 * The main RondoApp module.
 *
 * @type {angular.IModule}
 */
var rondo;
(function (rondo) {
    'use strict';
    var VERSION = '4';
    var RondoApp = angular.module('RondoApp', ['ngRoute', 'angularFileUpload']);
    RondoApp.controller('SongAddCtrl', rondo.SongAddCtrl);
    RondoApp.controller('SongDetailCtrl', rondo.SongDetailCtrl);
    RondoApp.controller('SongListCtrl', rondo.SongListCtrl);
    RondoApp.filter("yesno", rondo.filters.yesno);
    RondoApp.filter("encodeURIComponent", rondo.filters.encodeURIComponent);
    RondoApp.directive("status", rondo.directives.status);
    RondoApp.directive("license", rondo.directives.license);
    RondoApp.directive("licensetype", rondo.directives.licensetype);
    RondoApp.directive("yesno", rondo.directives.yesno);
    RondoApp.config(['$routeProvider',
        function ($routeProvider) {
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
})(rondo || (rondo = {}));
//# sourceMappingURL=app.js.map
(function() {
  'use strict';

  angular.module('app', ['irontec.simpleChat']);

  angular.module('app').controller('Shell', Shell);

  function Shell() {

    var vm = this;

    vm.messages = [
      {
        'username': 'Maymay',
        'content': 'Hi!'
      },
      {
        'username': 'Michele',
        'content': 'Whats up?'
      },
      {
        'username': 'James',
        'content': 'How can we help?'
      }
    ];

    vm.username = 'Survivor';

    vm.sendMessage = function(message, username) {
      if(message && message !== '' && username) {
        vm.messages.push({
          'username': username,
          'content': message
        });
      }
    };

  }

})();

/**
 * Module: TYPO3/CMS/QcInfoRights/ShowMembers
 *
 * @exports TYPO3/CMS/QcInforights/ShowMembers
 */
$(document).ready(function() {
  $('.showUsersLink').click(function (){
    var groupUid = $(this).attr('groupUid');
    // get the selected row
    var groupElement = document.getElementById('group'+groupUid);
    // show the selected members
    groupElement.className ='group'+groupUid+ " d-block";

    // to refresh the rendering data, we delete the previous rendering
    var child = groupElement.lastElementChild;
    while (child) {
      groupElement.removeChild(child);
      child = groupElement.lastElementChild;
    }
    require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
      var selectedColumn = $("#selectColumn :selected").val();
      new AjaxRequest(TYPO3.settings.ajaxUrls.show_members)
        .withQueryArguments({groupUid: groupUid, selectedColumn : selectedColumn})
        .get()
        .then(async function (response) {
          response.resolve().then(function (result){
            if(result != null){
              // get the translation messages for non provided value
              var realNameNotProvidedElement = $('#realNameNotProvided').data('tr-label')
              var emailNotProvidedElement = $('#emailNotProvided').data('tr-label')
              result.forEach(member => {
                var memberValue = member[selectedColumn]
                // if the column value doesn't exists
                if(memberValue === '' && selectedColumn === 'email'){
                  memberValue = member['username'] +  emailNotProvidedElement
                }
                else if(memberValue === '' && selectedColumn === 'realName'){
                  memberValue = member['username'] + realNameNotProvidedElement
                }
                // generate li element for each row
                var node = document.createElement("li");
                node.className = 'list-unstyled'
                node.style.marginTop = '5px'
                var textnode = document.createTextNode(memberValue);
                node.appendChild(textnode);
                groupElement.appendChild(node);
              })
            }
          }).catch(function(e){
            console.log(e)
          });
        });
    });
  })

});




function showMembers(e, groupUid) {
  e.preventDefault()
  // hide the previous member column
 /* var elements = document.getElementsByClassName('d-block');
  Array.prototype.forEach.call(elements, function(el) {
    el.className = el.className.replace('d-block', 'hidden');
  });*/

  var hideControlSpan = $('#hideMembersControl'+groupUid);
  var showMembersControlSpan = $('#showMembersControl'+groupUid);
  var show = true;
  if(showMembersControlSpan.attr('class') === undefined || showMembersControlSpan.attr('class') === ''){
    // request to show users
    showMembersControlSpan.attr('class', 'hidden');
    hideControlSpan.attr('class', '');
    var elements = document.getElementsByClassName('d-block');
    Array.prototype.forEach.call(elements, function(el) {
      el.className = el.className.replace('hidden','d-block');
    });

  }
  else{
    // request to hide users
    if(showMembersControlSpan.attr('class') === 'hidden'){
      showMembersControlSpan.attr('class', '');
      hideControlSpan.attr('class', 'hidden');
      var elements = document.getElementsByClassName('d-block');
      Array.prototype.forEach.call(elements, function(el) {
        el.className = el.className.replace('d-block', 'hidden');
      });
    }
    show = false;
  }





  if(show){

    // get the selected row
    var groupElement = document.getElementById('group'+groupUid);
    // show the selected members
    groupElement.className ='group'+groupUid+ " d-block";

    // to refresh the rendering data, we delete the previous rendering
    var child = groupElement.lastElementChild;
    while (child) {
      groupElement.removeChild(child);
      child = groupElement.lastElementChild;
    }
    require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
      var selectedColumn = $("#selectColumn :selected").val();
      new AjaxRequest(TYPO3.settings.ajaxUrls.show_members)
        .withQueryArguments({groupUid: groupUid, selectedColumn : selectedColumn})
        .get()
        .then(async function (response) {
          response.resolve().then(function (result){
            if(result != null){
              // get the translation messages for non provided value
              var realNameNotProvidedElement = $('#realNameNotProvided').data('tr-label')
              var emailNotProvidedElement = $('#emailNotProvided').data('tr-label')
              result.forEach(member => {
                var memberValue = member[selectedColumn]
                // if the column value doesn't exists
                if(memberValue === '' && selectedColumn === 'email'){
                  memberValue = member['username'] +  emailNotProvidedElement
                }
                else if(memberValue === '' && selectedColumn === 'realName'){
                  memberValue = member['username'] + realNameNotProvidedElement
                }
                // generate li element for each row
                var node = document.createElement("li");
                node.className = 'list-unstyled'
                node.style.marginTop = '5px'
                var textnode = document.createTextNode(memberValue);
                node.appendChild(textnode);
                groupElement.appendChild(node);
              })
            }
          }).catch(function(e){
            console.log(e)
          });
        });
    });
  }

}

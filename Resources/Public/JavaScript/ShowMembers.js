/**
 * Module: TYPO3/CMS/QcInfoRights/ShowMembers
 *
 * @exports TYPO3/CMS/QcInforights/ShowMembers
 */
function submitSelectedColumn(){
  // hide displaying columns
  var elements = document.getElementsByClassName('d-block');
  Array.prototype.forEach.call(elements, function(el) {
    el.className = el.className.replace('d-block', 'hidden');
  });
}
function showMembers(e, groupUid) {
  e.preventDefault()
  // hide the previous member column
  var elements = document.getElementsByClassName('d-block');
  Array.prototype.forEach.call(elements, function(el) {
    el.className = el.className.replace('d-block', 'hidden');
  });

  // get the selected row
  var groupElement = document.getElementById('group'+groupUid);
  // show the selected members
  groupElement.className ='group'+groupUid+ " d-block";

  // to refresh the rendring data, we delete the previous rendring
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
            result.forEach(member => {
              var memberValue = member[selectedColumn]
              // if the column value doesn't exists
              if(memberValue.includes('(') && memberValue.includes(')')){
                memberValue = member['username'] + ' ' +memberValue
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
        }).catch(function(){
          // show the "no members" message
          var noMemberElement = document.getElementsByClassName('noMembers'+groupUid);
          Array.prototype.forEach.call(noMemberElement, function(el) {
            el.className = el.className.replace('hidden', '');
          });
        });
      });
  });
}

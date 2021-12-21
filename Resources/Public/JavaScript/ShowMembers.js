/**
 * Module: TYPO3/CMS/QcInfoRights/ShowMembers
 *
 * @exports TYPO3/CMS/QcInforights/ShowMembers
 */
function submitSelectedColumn(){
  // hide displaying columns
  var elements = document.getElementsByClassName('members-show');
  Array.prototype.forEach.call(elements, function(el) {
    el.className = el.className.replace('members-show', 'members-hide');
  });
}
function showMembers(e, groupUid) {
  e.preventDefault()
  // hide the previous member column
  var elements = document.getElementsByClassName('members-show');
  Array.prototype.forEach.call(elements, function(el) {
    el.className = el.className.replace('members-show', 'members-hide');
  });

  // get the selected row
  var groupElement = document.getElementById('group'+groupUid);
  // show the selected members
  groupElement.className ='group'+groupUid+ " members-show";

  // to refresh the rendring data, we delete the previous rendring
  var child = groupElement.lastElementChild;
  while (child) {
    groupElement.removeChild(child);
    child = groupElement.lastElementChild;
  }
  require(['TYPO3/CMS/Core/Ajax/AjaxRequest'], function (AjaxRequest) {
    new AjaxRequest(TYPO3.settings.ajaxUrls.show_members)
      .withQueryArguments({groupUid: groupUid, selectedColumn : $("#selectColumn :selected").val()})
      .get()
      .then(async function (response) {
        console.log(response.resolve());
       /* var resolved = await response.resolve();
        var arrayOfMembers = JSON.parse(resolved)
        if(arrayOfMembers == 0){
         $('.noMembers'+groupUid).show()
        }
       else{
          arrayOfMembers.forEach($member => {
            var node = document.createElement("li");
            var textnode;
            textnode = document.createTextNode($member['name']);
            node.appendChild(textnode);
            groupElement.appendChild(node);
          })
        }*/
      });
  });
}

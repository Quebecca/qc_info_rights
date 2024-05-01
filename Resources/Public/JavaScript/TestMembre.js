import $ from 'jquery';


console.log("Testing only")
function submitSelectedColumn(){
  console.log("TEST JQUERY")
  var  showingElements =[];
  showingElements = $('.d-block');
  for(var i = 0; i < showingElements.length; i++){
    const classStr = showingElements[i].className.split(' ');
    classStr.forEach(item => {
      let groupUid;
      if (item.startsWith('group')) {
        groupUid = item.substr(5);
        resetShowMemberVisibility(groupUid)
      }
    })
  }
}

function showMembers(e, groupUid) {
  e.preventDefault()
  const show = resetShowMemberVisibility(groupUid);
  if(show){
    // get the selected row
    const groupElement = document.getElementById('group' + groupUid);
    // show the selected members
    groupElement.className ='group'+groupUid+ " d-block";
    // to refresh the rendering data, we delete the previous rendering
    let child = groupElement.lastElementChild;
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
                const textnode = document.createTextNode(memberValue);
                node.appendChild(textnode);
                groupElement.appendChild(node);
              })
            }
          });
        });
    });
  }
}

function resetShowMemberVisibility(groupUid){
  const membersElement = $('#group' + groupUid);
  const hideControlSpan = $('#hideMembersControl' + groupUid);
  const showMembersControlSpan = $('#showMembersControl' + groupUid);

  if (showMembersControlSpan.attr('class') === undefined || showMembersControlSpan.attr('class') === '') {
    // request to show users
    showMembersControlSpan.attr('class', 'hidden');
    hideControlSpan.attr('class', '');
    membersElement.attr('class', 'd-block')
  } else {
    // request to hide users
    if (showMembersControlSpan.attr('class') === 'hidden') {
      showMembersControlSpan.attr('class', '');
      hideControlSpan.attr('class', 'hidden');
      membersElement.attr('class', 'hidden')
    }
    return false;
  }
  return true;
}

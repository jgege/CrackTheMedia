
function getCurrentTabUrlAndTitle(callback) {
  var queryInfo = {
    active: true,
    currentWindow: true
  };

  chrome.tabs.query(queryInfo, function(tabs) {
    var tab = tabs[0];
    var url = tab.url;
    var title = tab.title;
    console.log(tab);

    callback(url, title);
  });
}

function showElement(element) {
  element.style.display = 'block';
}

function hideElement(element) {
  element.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  getCurrentTabUrlAndTitle(function(currentTabUrl, title) {
    hideElement(document.getElementById('baseinfo-wrapper'));
    hideElement(document.getElementById('article-wrapper'));

    /**
    * Knowledge search
    */
    var url = "http://crackthemedia.ml/gege/api/index?s=" + encodeURIComponent(currentTabUrl);
    var xhr = new XMLHttpRequest();

    xhr.open("GET", url, false);
    xhr.send();

    var result = xhr.responseText;
    var responseJson = JSON.parse(result);

    if (responseJson && responseJson.isOrganization) {
      console.log('Base info OK');
      showElement(document.getElementById('baseinfo-wrapper'));
      hideElement(document.getElementById('noinfo-wrapper'));
      var iconObj = document.getElementById("icon");
      var nameObj = document.getElementById("cont_name");
      var isOrgObj = document.getElementById("cont_is_organization");
      var descObj = document.getElementById("cont_description");

      iconObj.src = responseJson['image'];
      nameObj.innerHTML = responseJson['name'];
      descObj.innerHTML = responseJson['desc'];
      isOrgObj.innerHTML = (responseJson['isOrganization']) ? 'Organization' : 'Not an organization';
    }
    
    /**
    * Similarity check
    */
    var url = "http://crackthemedia.ml/gege/api/similarity?query=" + encodeURIComponent(title);
    var xhr = new XMLHttpRequest();

    xhr.open("GET", url, false);
    xhr.send();

    var result = xhr.responseText;
    var responseJson = JSON.parse(result);

    if (responseJson) {
      console.log('Article OK');
      showElement(document.getElementById('article-wrapper'));
      hideElement(document.getElementById('noinfo-wrapper'));
      var isSimilarObj = document.getElementById("is_similar_cont");
      var similarityScorebj = document.getElementById("similarity_score_cont");
      
      isSimilarObj.innerHTML = (responseJson['foundSimilarities']) ? "Found similarities" : "Couldn't find similarities";
      similarityScorebj.innerHTML = responseJson['score'];
    }
  });
});

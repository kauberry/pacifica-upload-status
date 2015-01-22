/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Utility functions
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function disable_element(el){
  if(el){
    el.setAttribute('disabled', 'disabled');
    if (!el.hasClassName('disabled_button')) {
        el.addClassName('disabled_button');
    }    
  }
}

function enable_element(el){
  if(el){
    el.removeAttribute('disabled');
    if (el.hasClassName('disabled_button')) {
        el.removeClassName('disabled_button');
    }    
  }
}

function isEmpty(str) {
    return (!str || 0 === str.length);
}

function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}
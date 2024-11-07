/* global jQuery */
(function ($) {
  // eslint-disable-next-line no-shadow
  $.entwine('ss', ($) => {
    $('.ss-gridfield .field .gridfield-dropdown').entwine({

      onchange() {
        const gridField = this.getGridField();
        const state = gridField.getState().GridFieldSiteTreeAddNewButton;

        state.recordType = this.val();
        gridField.setState('GridFieldSiteTreeAddNewButton', state);
      }
    });
  });
}(jQuery));

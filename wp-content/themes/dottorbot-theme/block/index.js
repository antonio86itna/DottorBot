(function(blocks, element){
    var el = element.createElement;
    blocks.registerBlockType('dottorbot/chat', {
        title: 'DottorBot Chat',
        icon: 'format-chat',
        category: 'widgets',
        edit: function(){
            return el('div', {}, 'DottorBot Chat placeholder');
        },
        save: function(){
            return null; // Rendered in PHP.
        }
    });
})(window.wp.blocks, window.wp.element);

'use strinct';

describe("BUOY main class", function() {
    it("counts number of menu items", function() {
        $(document.body).append('<div id="' + buoy_dom_hooks.menu_id + '> <a> test </a> <a> test2 </a> </div>')
        expect(BUOY.countIncidentMenuItems()).toBe(2)
    });
});

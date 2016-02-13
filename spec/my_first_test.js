describe("BUOY main class", function() {
  it("counts number of menu items", function() {
    $(document.body).append("<div id='wp-admin-bar-buoy-alert-menu'> <a> test </a> <a> test2 </a> </div>")
    expect(BUOY.countIncidentMenuItems()).toBe(2)
  });
});
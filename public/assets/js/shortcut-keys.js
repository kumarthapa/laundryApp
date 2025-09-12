// Function to handle key press events
function handleKeyPress(event) {
  console.log('key predded', event);
  // Check if 'A' key is pressed
  if (event.ctrlKey && (event.key === 'x' || event.key === 'X')) {
    // Simulate a click on Link A
    document.getElementById('easy_dashboard_nav_link').click();
  }
  // Check if 'B' key is pressed
  // else if (event.key === 'b' || event.key === 'B') {
  //   // Simulate a click on Link B
  //   document.getElementById('linkB').click();
  // }
}
console.log('shortcuts loaded');
// Event listener for key press events on the whole document
document.addEventListener('keypress', handleKeyPress);

function generateRandomString(length, prefix = '', options = {}) {
  let charset = '';
  if (options?.useNumbers ?? true) charset += '0123456789';
  if (options?.useCapitals ?? true) charset += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  if (options?.useLowercase) charset += 'abcdefghijklmnopqrstuvwxyz';

  let result = prefix;
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * charset.length);
    result += charset[randomIndex];
  }

  return result;
}

function generateTripId(tour_id, customer_code, location_code) {
  // Get the current date
  const now = new Date();

  // Calculate the week number of the year
  const startOfYear = new Date(now.getFullYear(), 0, 1);
  const weekNumber = Math.ceil((now - startOfYear) / 1000 / 60 / 60 / 24 + startOfYear.getDay() + 1) / 7;

  // Get the day number of the week (1 = Monday, 7 = Sunday)
  const dayNumber = now.getDay() === 0 ? 7 : now.getDay(); // Sunday is 0 in JavaScript

  let trip_id = `${weekNumber}${dayNumber}`;
}

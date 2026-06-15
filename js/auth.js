// Authentication and database helpers backed by a PHP/MySQL API

async function requestJson(endpoint, options = {}) {
  if (window.location.protocol === 'file:') {
    throw new Error('Please open the project through a local PHP server such as XAMPP, WAMP, or Laragon. Opening the HTML files directly from the file system will not run the MySQL API.');
  }

  const apiBaseUrl = window.API_BASE_URL || (window.location.pathname.includes('/pages/') ? '../api' : 'api');

  let response;
  try {
    response = await fetch(`${apiBaseUrl}/${endpoint}`, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      },
      ...options
    });
  } catch (error) {
    throw new Error('The authentication server could not be reached. Make sure your PHP/MySQL server is running and the project is being served from localhost.');
  }

  const text = await response.text();
  let payload = {};

  try {
    payload = text ? JSON.parse(text) : {};
  } catch (error) {
    payload = {};
  }

  if (!response.ok) {
    throw new Error(payload.error || 'Request failed');
  }

  return payload;
}

const Auth = {
  async register(email, password, fullName, userId) {
    try {
      const result = await requestJson('auth.php?action=register', {
        method: 'POST',
        body: JSON.stringify({
          email,
          password,
          full_name: fullName,
          user_id: userId
        })
      });

      return { success: true, user: result.user };
    } catch (error) {
      console.error('Registration error:', error);
      return { success: false, error: error.message };
    }
  },

  async login(email, password) {
    try {
      const result = await requestJson('auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({
          email,
          user_id: email,
          password
        })
      });

      return { success: true, user: result.user };
    } catch (error) {
      console.error('Login error:', error);
      return { success: false, error: error.message };
    }
  },

  async logout() {
    try {
      await requestJson('auth.php?action=logout', { method: 'POST' });
      return { success: true };
    } catch (error) {
      console.error('Logout error:', error);
      return { success: false, error: error.message };
    }
  },

  async getCurrentUser() {
    try {
      const result = await requestJson('auth.php?action=me');
      return result.user || null;
    } catch (error) {
      console.error('Get user error:', error);
      return null;
    }
  },

  async resetPassword(email) {
    try {
      await requestJson('auth.php?action=reset', {
        method: 'POST',
        body: JSON.stringify({ email })
      });
      return { success: true };
    } catch (error) {
      console.error('Reset password error:', error);
      return { success: false, error: error.message };
    }
  }
};

const Database = {
  async getBookings() {
    try {
      const result = await requestJson('bookings.php?action=getBookings');
      return Array.isArray(result) ? result : [];
    } catch (error) {
      console.error('Get bookings error:', error);
      return [];
    }
  },

  async getAllBookings() {
    try {
      const result = await requestJson('bookings.php?action=getAllBookings');
      return Array.isArray(result) ? result : [];
    } catch (error) {
      console.error('Get all bookings error:', error);
      return [];
    }
  },

  async createBooking(bookingData) {
    try {
      const user = await Auth.getCurrentUser();
      if (!user) throw new Error('User not authenticated');

      const booking = {
        user_id: user.id,
        lab: bookingData.lab,
        date: bookingData.date,
        time: bookingData.time,
        time_out: bookingData.time_out,
        system: bookingData.system,
        status: 'pending'
      };

      const result = await requestJson('bookings.php?action=createBooking', {
        method: 'POST',
        body: JSON.stringify(booking)
      });

      return { success: true, booking: result.booking };
    } catch (error) {
      console.error('Create booking error:', error);
      return { success: false, error: error.message };
    }
  },

  async updateBookingStatus(bookingId, status) {
    try {
      await requestJson('bookings.php?action=updateBookingStatus', {
        method: 'POST',
        body: JSON.stringify({ id: bookingId, status })
      });
      return { success: true };
    } catch (error) {
      console.error('Update booking error:', error);
      return { success: false, error: error.message };
    }
  },

  async deleteBooking(bookingId) {
    try {
      await requestJson('bookings.php?action=deleteBooking', {
        method: 'POST',
        body: JSON.stringify({ id: bookingId })
      });
      return { success: true };
    } catch (error) {
      console.error('Delete booking error:', error);
      return { success: false, error: error.message };
    }
  },

  async countPendingBookings() {
    try {
      const result = await requestJson('bookings.php?action=countPendingBookings');
      return Number(result.count || 0);
    } catch (error) {
      console.error('Count pending bookings error:', error);
      return 0;
    }
  },

  async expireOldPendingBookings() {
    try {
      await requestJson('bookings.php?action=expireOldPendingBookings', { method: 'POST' });
    } catch (error) {
      console.error('Expire pending bookings error:', error);
    }
  },

  async getLabs() {
    try {
      const result = await requestJson('bookings.php?action=getLabs');
      return Array.isArray(result) ? result : [];
    } catch (error) {
      console.error('Get labs error:', error);
      return [
        { name: 'Lab A', capacity: 15, computers: 15, type: 'small', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab B', capacity: 20, computers: 20, type: 'small', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab C', capacity: 25, computers: 25, type: 'medium', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab D', capacity: 30, computers: 30, type: 'medium', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab E', capacity: 35, computers: 35, type: 'medium', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab F', capacity: 40, computers: 40, type: 'medium', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab G', capacity: 45, computers: 45, type: 'large', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab H', capacity: 50, computers: 50, type: 'large', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab I', capacity: 60, computers: 60, type: 'large', status: 'available', building: 'Southwing', floor: '5th Floor' },
        { name: 'Lab J', capacity: 80, computers: 80, type: 'large', status: 'available', building: 'Southwing', floor: '5th Floor' }
      ];
    }
  }
};

window.Auth = Auth;
window.Database = Database;

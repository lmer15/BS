// Dashboard JavaScript - Real Backend Integration
console.log('Dashboard JS loaded');

// Global variables
let currentUser = null;
let currentBills = [];
let currentBill = null;
let currentBillId = null;
  
  // DOM Elements
  const groupList = document.getElementById('group-list');
  const memberList = document.getElementById('member-list');
  const activityList = document.getElementById('activity-list');
  const balancesList = document.getElementById('balances-list');
  const currentGroupName = document.getElementById('current-group-name');
  const balanceAmount = document.querySelector('.balance-amount');
  const addExpenseBtn = document.getElementById('add-expense-btn');
  const settleUpBtn = document.getElementById('settle-up-btn');
  const addMemberBtn = document.getElementById('add-member-btn');
  const newGroupBtn = document.getElementById('new-group-btn');
  const newGroupModal = document.getElementById('new-group-modal');
const newGroupForm = document.getElementById('new-group-form');
  const closeButtons = document.querySelectorAll('.close-btn');

// Initialize dashboard
async function init() {
    console.log('Initializing dashboard...');
    try {
        await loadDashboardData();
        setupEventListeners();
        renderBillList();
        console.log('Dashboard initialized successfully');
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
        showError('Failed to load dashboard data: ' + error.message);
    }
}

// Load dashboard data from backend
async function loadDashboardData() {
    console.log('Loading dashboard data...');
    try {
        const response = await fetch('../Controller/DashboardController.php?action=get_data', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            if (response.status === 401) {
                console.log('Not authenticated, redirecting to login');
                window.location.href = 'login.html';
                return;
            }
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Dashboard data received:', data);
        
        if (data.success) {
            currentUser = data.user;
            currentBills = data.bills || [];
            updateUserProfile();
        } else {
            throw new Error(data.message || 'Failed to load dashboard data');
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        throw error;
    }
}

// Update user profile in header
function updateUserProfile() {
    if (currentUser) {
        const userProfile = document.querySelector('.user-profile span');
        if (userProfile) {
            userProfile.textContent = currentUser.first_name || 'User';
        }
        console.log('User profile updated:', currentUser.first_name);
    }
}

// Render bill list
function renderBillList() {
    console.log('Rendering bill list with', currentBills.length, 'bills');
    groupList.innerHTML = '';
    
    if (currentBills.length === 0) {
        groupList.innerHTML = '<li class="empty-state">No bills yet. Create one to get started!</li>';
      return;
    }
    
    currentBills.forEach(bill => {
        const billItem = document.createElement('li');
        billItem.className = 'group-item';
        billItem.dataset.billId = bill.id;
        
        const totalOwed = parseFloat(bill.total_owed || 0);
        const balanceClass = totalOwed > 0 ? 'positive' : totalOwed < 0 ? 'negative' : 'neutral';
        
        billItem.innerHTML = `
            <span class="group-name">${bill.title}</span>
            <span class="group-balance ${balanceClass}">
                ${formatCurrency(totalOwed, 'PHP')}
        </span>
      `;
      
        groupList.appendChild(billItem);
    });
}

// Load specific bill details
async function loadBillDetails(billId) {
    console.log('Loading bill details for ID:', billId);
    try {
        const response = await fetch(`../Controller/BillController.php?action=details&bill_id=${billId}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Bill details received:', data);
        
        if (data.success) {
            currentBill = data.bill;
            currentBillId = billId;
            renderBillDetails();
        } else {
            throw new Error(data.message || 'Failed to load bill details');
        }
    } catch (error) {
        console.error('Error loading bill details:', error);
        showError('Failed to load bill details: ' + error.message);
    }
}

// Render bill details
function renderBillDetails() {
    console.log('Rendering bill details for:', currentBill.title);
    if (!currentBill) return;
    
    // Update bill name
    currentGroupName.textContent = currentBill.title;
    
    // Enable buttons
    addExpenseBtn.disabled = false;
    settleUpBtn.disabled = false;
    addMemberBtn.disabled = false;
    
    // Render participants
    renderParticipantList();
    
    // Calculate and render balances
    renderBalances();
    
    // Render recent activity
    renderActivityList();
  }
  
// Render participant list
function renderParticipantList() {
    memberList.innerHTML = '';
    
    if (!currentBill.participants || currentBill.participants.length === 0) {
        memberList.innerHTML = '<li class="empty-state">No participants yet</li>';
        return;
    }
    
    currentBill.participants.forEach(participant => {
      const memberItem = document.createElement('li');
      memberItem.className = 'member-item';
        
        const name = participant.user_id ? 
            `${participant.first_name} ${participant.last_name}` : 
            participant.guest_name;
        
        const amountOwed = parseFloat(participant.amount_owed || 0);
        const balanceClass = amountOwed > 0 ? 'positive' : amountOwed < 0 ? 'negative' : 'neutral';
      
      memberItem.innerHTML = `
        <div class="member-info">
                <img src="https://i.pravatar.cc/40?u=${participant.id}" alt="${name}" class="profile-pic">
                <span class="member-name">${name}</span>
        </div>
            <span class="member-balance ${balanceClass}">
                ${formatCurrency(amountOwed, 'PHP')}
        </span>
      `;
      
      memberList.appendChild(memberItem);
    });
  }
  
// Render balances
function renderBalances() {
    if (!currentBill) return;
    
    const totalAmount = parseFloat(currentBill.total_amount || 0);
    const totalOwed = currentBill.participants ? 
        currentBill.participants.reduce((sum, p) => sum + parseFloat(p.amount_owed || 0), 0) : 0;
    
    const yourBalance = totalOwed - totalAmount;
    const balanceClass = yourBalance > 0 ? 'positive' : yourBalance < 0 ? 'negative' : 'neutral';
    
    balanceAmount.textContent = formatCurrency(yourBalance, 'PHP');
    balanceAmount.className = `balance-amount ${balanceClass}`;
    
    // Simplified balances
    balancesList.innerHTML = '';
    if (currentBill.participants && currentBill.participants.length > 0) {
        currentBill.participants.forEach(participant => {
            const amountOwed = parseFloat(participant.amount_owed || 0);
            if (amountOwed > 0) {
                const li = document.createElement('li');
                const name = participant.user_id ? 
                    `${participant.first_name} ${participant.last_name}` : 
                    participant.guest_name;
                li.textContent = `${name} owes ${formatCurrency(amountOwed, 'PHP')}`;
                balancesList.appendChild(li);
            }
        });
      } else {
        balancesList.innerHTML = '<li class="empty-state">No balances to show</li>';
      }
  }
  
  // Render activity list
function renderActivityList() {
    activityList.innerHTML = '';
    
    if (!currentBill.participants || currentBill.participants.length === 0) {
        activityList.innerHTML = '<li class="empty-state">No activity yet. Add participants to get started!</li>';
      return;
    }
    
    // For now, show bill creation as activity
      const activityItem = document.createElement('li');
      activityItem.className = 'activity-item';
      
      activityItem.innerHTML = `
        <div class="activity-icon">💸</div>
        <div class="activity-details">
            <p>Bill "${currentBill.title}" created</p>
            <small>${formatDate(currentBill.created_at)}</small>
        </div>
        <div class="activity-amount neutral">
            ${formatCurrency(currentBill.total_amount, 'PHP')}
        </div>
      `;
      
      activityList.appendChild(activityItem);
}

// Create new bill
async function createNewBill() {
    const name = document.getElementById('group-name').value;
    const currency = document.getElementById('group-currency').value;
    
    console.log('Creating new bill:', name, currency);
    
    try {
        const response = await fetch('../Controller/BillController.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                title: name,
                description: '',
                total_amount: 0
            })
        });
        
        const data = await response.json();
        console.log('Create bill response:', data);
        
        if (data.success) {
            await loadDashboardData();
            renderBillList();
            newGroupModal.classList.remove('active');
            newGroupForm.reset();
            showSuccess('Bill created successfully!');
        } else {
            throw new Error(data.message || 'Failed to create bill');
        }
    } catch (error) {
        console.error('Error creating bill:', error);
        showError('Failed to create bill: ' + error.message);
    }
}

// Add participant to bill
async function addParticipant() {
    if (!currentBillId) return;
    
    const guestName = prompt('Enter guest name:');
    const guestEmail = prompt('Enter guest email:');
    const amountOwed = parseFloat(prompt('Enter amount owed:') || 0);
    
    if (!guestName || !guestEmail || isNaN(amountOwed)) {
        showError('Please provide valid guest information');
      return;
    }
    
    console.log('Adding participant:', guestName, guestEmail, amountOwed);
    
    try {
        const response = await fetch('../Controller/BillController.php?action=add_participant', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                bill_id: currentBillId,
                guest_name: guestName,
                guest_email: guestEmail,
                amount_owed: amountOwed
            })
        });
        
        const data = await response.json();
        console.log('Add participant response:', data);
        
        if (data.success) {
            await loadBillDetails(currentBillId);
            showSuccess('Participant added successfully!');
        } else {
            throw new Error(data.message || 'Failed to add participant');
        }
    } catch (error) {
        console.error('Error adding participant:', error);
        showError('Failed to add participant: ' + error.message);
    }
}

// Setup event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');
    console.log('newGroupBtn element:', newGroupBtn);
    console.log('newGroupModal element:', newGroupModal);
    
    // Bill selection
    groupList.addEventListener('click', (e) => {
        const billItem = e.target.closest('.group-item');
        if (billItem) {
            const billId = parseInt(billItem.dataset.billId);
            loadBillDetails(billId);
            
            // Update active state
            document.querySelectorAll('.group-item').forEach(item => {
                item.classList.remove('active');
            });
            billItem.classList.add('active');
        }
    });
    
    // New bill button
    newGroupBtn.addEventListener('click', (e) => {
        console.log('New group button clicked', e);
        console.log('Modal element:', newGroupModal);
        newGroupModal.classList.add('active');
        console.log('Modal active class added');
    });
    
    // Add participant button
    addMemberBtn.addEventListener('click', addParticipant);
    
    // Close modals
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal').classList.remove('active');
        });
    });
    
    // Click outside modal to close
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // New bill form submission
    newGroupForm.addEventListener('submit', (e) => {
        e.preventDefault();
        createNewBill();
    });
  }
  
  // Helper functions
  function formatCurrency(amount, currency) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
        currency: currency || 'PHP',
      minimumFractionDigits: 2
    }).format(amount).replace(/^(\D+)/, '$1 ');
  }
  
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined 
    });
  }
  
// Toast notification system
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <div class="toast-icon ${type}">${icons[type] || icons.info}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="removeToast(this.parentElement)">×</button>
        <div class="toast-progress">
            <div class="toast-progress-bar"></div>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto remove after duration
    setTimeout(() => {
        removeToast(toast);
    }, duration);
}

function removeToast(toast) {
    if (!toast) return;
    
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

function showError(message) {
    console.error('Error:', message);
    showToast('error', 'Error', message);
}

function showSuccess(message) {
    console.log('Success:', message);
    showToast('success', 'Success', message);
}

function showWarning(message) {
    console.warn('Warning:', message);
    showToast('warning', 'Warning', message);
}

function showInfo(message) {
    console.log('Info:', message);
    showToast('info', 'Info', message);
}

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing dashboard...');
    init();
});
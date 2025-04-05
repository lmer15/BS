// Sample Data
const sampleGroups = [
    {
      id: 1,
      name: "Roommates",
      currency: "PHP",
      members: [
        { id: 1, name: "You", email: "you@example.com", avatar: "https://i.pravatar.cc/40?u=1" },
        { id: 2, name: "Alex", email: "alex@example.com", avatar: "https://i.pravatar.cc/40?u=2" },
        { id: 3, name: "Sam", email: "sam@example.com", avatar: "https://i.pravatar.cc/40?u=3" }
      ],
      expenses: [
        {
          id: 1,
          description: "Groceries",
          amount: 50.00,
          payer: 1,
          date: "2023-06-15",
          category: "food",
          split: {
            method: "equal",
            shares: { 1: 16.67, 2: 16.67, 3: 16.67 }
          }
        },
        {
          id: 2,
          description: "Electricity Bill",
          amount: 75.00,
          payer: 2,
          date: "2023-06-10",
          category: "housing",
          split: {
            method: "custom",
            shares: { 1: 25.00, 2: 25.00, 3: 25.00 }
          }
        }
      ]
    },
    {
      id: 2,
      name: "Vacation 2023",
      currency: "PHP",
      members: [
        { id: 1, name: "You", email: "you@example.com", avatar: "https://i.pravatar.cc/40?u=1" },
        { id: 4, name: "Taylor", email: "taylor@example.com", avatar: "https://i.pravatar.cc/40?u=4" },
        { id: 5, name: "Jordan", email: "jordan@example.com", avatar: "https://i.pravatar.cc/40?u=5" }
      ],
      expenses: [
        {
          id: 1,
          description: "Hotel Booking",
          amount: 300.00,
          payer: 1,
          date: "2023-05-20",
          category: "housing",
          split: {
            method: "equal",
            shares: { 1: 100.00, 4: 100.00, 5: 100.00 }
          }
        }
      ]
    }
  ];
  
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
  const expenseModal = document.getElementById('expense-modal');
  const closeButtons = document.querySelectorAll('.close-btn');
  const newGroupForm = document.getElementById('new-group-form');
  const expenseForm = document.getElementById('expense-form');
  const expensePayerSelect = document.getElementById('expense-payer');
  const splitDetails = document.getElementById('split-details');
  const splitMethodBtns = document.querySelectorAll('.split-method-btn');
  const expenseChartCtx = document.getElementById('expense-chart').getContext('2d');
  
  // State
  let currentGroupId = null;
  let expenseChart = null;
  
  // Initialize the app
  function init() {
    renderGroupList();
    setupEventListeners();
  }
  
  // Render group list in sidebar
  function renderGroupList() {
    groupList.innerHTML = '';
    
    if (sampleGroups.length === 0) {
      groupList.innerHTML = '<li class="empty-state">No groups yet. Create one to get started!</li>';
      return;
    }
    
    sampleGroups.forEach(group => {
      const balanceInfo = calculateBalances(group);
      const yourBalance = balanceInfo.yourBalance;
      
      const groupItem = document.createElement('li');
      groupItem.className = 'group-item';
      groupItem.dataset.groupId = group.id;
      
      groupItem.innerHTML = `
        <span class="group-name">${group.name}</span>
        <span class="group-balance ${yourBalance > 0 ? 'positive' : yourBalance < 0 ? 'negative' : 'neutral'}">
          ${yourBalance > 0 ? '+' : ''}${formatCurrency(yourBalance, group.currency)}
        </span>
      `;
      
      groupList.appendChild(groupItem);
    });
  }
  
  // Setup event listeners
  function setupEventListeners() {
    // Group selection
    groupList.addEventListener('click', (e) => {
      const groupItem = e.target.closest('.group-item');
      if (groupItem) {
        const groupId = parseInt(groupItem.dataset.groupId);
        loadGroupData(groupId);
        
        // Update active state
        document.querySelectorAll('.group-item').forEach(item => {
          item.classList.remove('active');
        });
        groupItem.classList.add('active');
      }
    });
    
    // New group button
    newGroupBtn.addEventListener('click', () => {
      newGroupModal.style.display = 'flex';
    });
    
    // Add expense button
    addExpenseBtn.addEventListener('click', () => {
      if (currentGroupId) {
        prepareExpenseForm();
        expenseModal.style.display = 'flex';
      }
    });
    
    // Close modals
    closeButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        btn.closest('.modal').style.display = 'none';
      });
    });
    
    // Click outside modal to close
    window.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
      }
    });
    
    // Split method buttons
    splitMethodBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        splitMethodBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        updateSplitDetails();
      });
    });
    
    // New group form submission
    newGroupForm.addEventListener('submit', (e) => {
      e.preventDefault();
      createNewGroup();
    });
    
    // Expense form submission
    expenseForm.addEventListener('submit', (e) => {
      e.preventDefault();
      addNewExpense();
    });
  }
  
  // Load data for a specific group
  function loadGroupData(groupId) {
    currentGroupId = groupId;
    const group = sampleGroups.find(g => g.id === groupId);
    
    if (!group) return;
    
    // Update group name
    currentGroupName.textContent = group.name;
    
    // Enable buttons
    addExpenseBtn.disabled = false;
    settleUpBtn.disabled = false;
    addMemberBtn.disabled = false;
    
    // Render members
    renderMemberList(group);
    
    // Calculate and render balances
    const balanceInfo = calculateBalances(group);
    renderBalances(balanceInfo, group.currency);
    
    // Render recent activity
    renderActivityList(group);
    
    // Render expense chart
    renderExpenseChart(group);
  }
  
  // Render member list
  function renderMemberList(group) {
    memberList.innerHTML = '';
    
    group.members.forEach(member => {
      const balanceInfo = calculateBalances(group);
      const memberBalance = balanceInfo.balances[member.id] || 0;
      
      const memberItem = document.createElement('li');
      memberItem.className = 'member-item';
      
      memberItem.innerHTML = `
        <div class="member-info">
          <img src="${member.avatar}" alt="${member.name}" class="profile-pic">
          <span class="member-name">${member.name}</span>
        </div>
        <span class="member-balance ${memberBalance > 0 ? 'positive' : memberBalance < 0 ? 'negative' : 'neutral'}">
          ${memberBalance > 0 ? '+' : ''}${formatCurrency(memberBalance, group.currency)}
        </span>
      `;
      
      memberList.appendChild(memberItem);
    });
  }
  
  // Calculate balances for a group
  function calculateBalances(group) {
    const balances = {};
    let yourBalance = 0;
    
    // Initialize balances
    group.members.forEach(member => {
      balances[member.id] = 0;
    });
    
    // Calculate balances from expenses
    group.expenses.forEach(expense => {
      const payerId = expense.payer;
      const amount = expense.amount;
      
      // Add to payer's balance
      balances[payerId] = (balances[payerId] || 0) + amount;
      
      // Subtract from each participant's balance
      Object.entries(expense.split.shares).forEach(([memberId, share]) => {
        balances[parseInt(memberId)] = (balances[parseInt(memberId)] || 0) - share;
      });
    });
    
    // Calculate your balance (assuming member with id 1 is "You")
    yourBalance = balances[1] || 0;
    
    return { balances, yourBalance, simplified: simplifyBalances(balances, group.members) };
  }
  
  // Simplify balances to minimize transactions
  function simplifyBalances(balances, members) {
    const creditors = [];
    const debtors = [];
    
    // Separate creditors and debtors
    Object.entries(balances).forEach(([id, balance]) => {
      const member = members.find(m => m.id === parseInt(id));
      if (balance > 0) {
        creditors.push({ id: parseInt(id), name: member.name, amount: balance });
      } else if (balance < 0) {
        debtors.push({ id: parseInt(id), name: member.name, amount: -balance });
      }
    });
    
    // Sort by amount (descending)
    creditors.sort((a, b) => b.amount - a.amount);
    debtors.sort((a, b) => b.amount - a.amount);
    
    const transactions = [];
    let i = 0, j = 0;
    
    while (i < creditors.length && j < debtors.length) {
      const creditor = creditors[i];
      const debtor = debtors[j];
      
      const minAmount = Math.min(creditor.amount, debtor.amount);
      
      transactions.push({
        from: debtor.id,
        to: creditor.id,
        fromName: debtor.name,
        toName: creditor.name,
        amount: minAmount
      });
      
      creditor.amount -= minAmount;
      debtor.amount -= minAmount;
      
      if (creditor.amount === 0) i++;
      if (debtor.amount === 0) j++;
    }
    
    return transactions;
  }
  
  // Render balances
  function renderBalances(balanceInfo, currency) {
    // Update your balance
    balanceAmount.textContent = formatCurrency(balanceInfo.yourBalance, currency);
    balanceAmount.className = `balance-amount ${balanceInfo.yourBalance > 0 ? 'positive' : balanceInfo.yourBalance < 0 ? 'negative' : 'neutral'}`;
    
    // Update simplified balances
    balancesList.innerHTML = '';
    
    if (balanceInfo.simplified.length === 0) {
      balancesList.innerHTML = '<li class="empty-state">All settled up!</li>';
      return;
    }
    
    balanceInfo.simplified.forEach(tx => {
      const li = document.createElement('li');
      
      if (tx.from === 1) {
        li.textContent = `You owe ${tx.toName} ${formatCurrency(tx.amount, currency)}`;
      } else if (tx.to === 1) {
        li.textContent = `${tx.fromName} owes you ${formatCurrency(tx.amount, currency)}`;
      } else {
        li.textContent = `${tx.fromName} owes ${tx.toName} ${formatCurrency(tx.amount, currency)}`;
      }
      
      balancesList.appendChild(li);
    });
  }
  
  // Render activity list
  function renderActivityList(group) {
    activityList.innerHTML = '';
    
    if (group.expenses.length === 0) {
      activityList.innerHTML = '<li class="empty-state">No activity yet. Add an expense to get started!</li>';
      return;
    }
    
    // Sort expenses by date (newest first)
    const sortedExpenses = [...group.expenses].sort((a, b) => new Date(b.date) - new Date(a.date));
    
    sortedExpenses.forEach(expense => {
      const payer = group.members.find(m => m.id === expense.payer);
      const isYou = payer.id === 1;
      const categoryIcon = getCategoryIcon(expense.category);
      
      const activityItem = document.createElement('li');
      activityItem.className = 'activity-item';
      
      // Calculate your share (assuming member id 1 is "You")
      const yourShare = expense.split.shares[1] || 0;
      
      activityItem.innerHTML = `
        <div class="activity-icon">${categoryIcon}</div>
        <div class="activity-details">
          <p>${isYou ? 'You' : payer.name} paid ${formatCurrency(expense.amount, group.currency)} for "${expense.description}"</p>
          <small>${formatDate(expense.date)}</small>
        </div>
        <div class="activity-amount ${yourShare > 0 ? 'negative' : 'neutral'}">
          ${yourShare > 0 ? `-${formatCurrency(yourShare, group.currency)}` : '—'}
        </div>
      `;
      
      activityList.appendChild(activityItem);
    });
  }
  
  // Prepare expense form with current group data
  function prepareExpenseForm() {
    if (!currentGroupId) return;
    
    const group = sampleGroups.find(g => g.id === currentGroupId);
    if (!group) return;
    
    // Reset form
    expenseForm.reset();
    expensePayerSelect.innerHTML = '';
    
    // Set today's date as default
    document.getElementById('expense-date').valueAsDate = new Date();
    
    // Populate payer dropdown
    group.members.forEach(member => {
      const option = document.createElement('option');
      option.value = member.id;
      option.textContent = member.name;
      expensePayerSelect.appendChild(option);
    });
    
    // Default to equal split
    updateSplitDetails();
  }
  
  // Update split details based on selected method
  function updateSplitDetails() {
    if (!currentGroupId) return;
    
    const group = sampleGroups.find(g => g.id === currentGroupId);
    if (!group) return;
    
    const selectedMethod = document.querySelector('.split-method-btn.active').dataset.method;
    splitDetails.innerHTML = '';
    
    if (selectedMethod === 'equal') {
      splitDetails.innerHTML = `
        <p>This expense will be split equally between all ${group.members.length} members.</p>
        <p>Each member will owe ${formatCurrency(1 / group.members.length * 100, group.currency)}% of the total.</p>
      `;
    } else if (selectedMethod === 'percentage') {
      let percentageInputs = '';
      group.members.forEach(member => {
        percentageInputs += `
          <div class="percentage-row">
            <label>${member.name}</label>
            <input type="number" min="0" max="100" value="${Math.round(100 / group.members.length)}" class="percentage-input" data-member-id="${member.id}">
            <span>%</span>
          </div>
        `;
      });
      
      splitDetails.innerHTML = `
        <p>Enter the percentage each member should pay:</p>
        ${percentageInputs}
        <p class="percentage-total">Total: 100%</p>
      `;
    } else if (selectedMethod === 'custom') {
      let customInputs = '';
      group.members.forEach(member => {
        customInputs += `
          <div class="custom-row">
            <label>${member.name}</label>
            <input type="number" min="0" step="0.01" value="0.00" class="custom-input" data-member-id="${member.id}">
            <span>${group.currency}</span>
          </div>
        `;
      });
      
      splitDetails.innerHTML = `
        <p>Enter the exact amount each member should pay:</p>
        ${customInputs}
        <p class="custom-total">Total: <span>0.00</span> ${group.currency}</p>
      `;
    }
  }
  
  // Add new expense
  function addNewExpense() {
    if (!currentGroupId) return;
    
    const group = sampleGroups.find(g => g.id === currentGroupId);
    if (!group) return;
    
    const description = document.getElementById('expense-description').value;
    const amount = parseFloat(document.getElementById('expense-amount').value);
    const payer = parseInt(document.getElementById('expense-payer').value);
    const category = document.getElementById('expense-category').value;
    const date = document.getElementById('expense-date').value;
    const method = document.querySelector('.split-method-btn.active').dataset.method;
    
    // Calculate shares based on split method
    const shares = {};
    let totalShares = 0;
    
    if (method === 'equal') {
      const share = amount / group.members.length;
      group.members.forEach(member => {
        shares[member.id] = share;
      });
      totalShares = amount;
    } else if (method === 'percentage') {
      const percentageInputs = document.querySelectorAll('.percentage-input');
      percentageInputs.forEach(input => {
        const percentage = parseFloat(input.value) / 100;
        const memberId = parseInt(input.dataset.memberId);
        shares[memberId] = amount * percentage;
        totalShares += amount * percentage;
      });
    } else if (method === 'custom') {
      const customInputs = document.querySelectorAll('.custom-input');
      customInputs.forEach(input => {
        const value = parseFloat(input.value);
        const memberId = parseInt(input.dataset.memberId);
        shares[memberId] = value;
        totalShares += value;
      });
    }
    
    // Validate total shares match expense amount
    if (Math.abs(totalShares - amount) > 0.01) {
      alert('The sum of shares must equal the expense amount');
      return;
    }
    
    // Create new expense
    const newExpense = {
      id: group.expenses.length + 1,
      description,
      amount,
      payer,
      date,
      category,
      split: {
        method,
        shares
      }
    };
    
    // Add to group's expenses
    group.expenses.push(newExpense);
    
    // Reload group data
    loadGroupData(currentGroupId);
    
    // Close modal
    expenseModal.style.display = 'none';
  }
  
  // Create new group
  function createNewGroup() {
    const name = document.getElementById('group-name').value;
    const currency = document.getElementById('group-currency').value;
    
    // Create new group
    const newGroup = {
      id: sampleGroups.length + 1,
      name,
      currency,
      members: [
        { id: 1, name: "You", email: "you@example.com", avatar: "https://i.pravatar.cc/40?u=1" }
      ],
      expenses: []
    };
    
    // Add to groups
    sampleGroups.push(newGroup);
    
    // Reload group list
    renderGroupList();
    
    // Close modal
    newGroupModal.style.display = 'none';
  }
  
  // Render expense chart
  function renderExpenseChart(group) {
    // Destroy previous chart if exists
    if (expenseChart) {
      expenseChart.destroy();
    }
    
    // Group expenses by category
    const categories = {};
    group.expenses.forEach(expense => {
      if (!categories[expense.category]) {
        categories[expense.category] = 0;
      }
      categories[expense.category] += expense.amount;
    });
    
    // Prepare data for chart
    const labels = Object.keys(categories).map(cat => {
      switch (cat) {
        case 'food': return '🍕 Food';
        case 'transport': return '🚗 Transport';
        case 'shopping': return '🛍️ Shopping';
        case 'housing': return '🏠 Housing';
        case 'entertainment': return '🎬 Entertainment';
        default: return '❓ Other';
      }
    });
    
    const data = Object.values(categories);
    const backgroundColors = [
      '#4285f4', '#34a853', '#fbbc05', '#ea4335', '#673ab7', '#9e9e9e'
    ].slice(0, labels.length);
    
    // Create chart
    expenseChart = new Chart(expenseChartCtx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: backgroundColors,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.raw;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${context.label}: ${formatCurrency(value, group.currency)} (${percentage}%)`;
              }
            }
          }
        }
      }
    });
  }
  
  // Helper functions
  function formatCurrency(amount, currency) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD',
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
  
  function getCategoryIcon(category) {
    switch (category) {
      case 'food': return '🍕';
      case 'transport': return '🚗';
      case 'shopping': return '🛍️';
      case 'housing': return '🏠';
      case 'entertainment': return '🎬';
      default: return '💸';
    }
  }
  
  // Initialize the app
  document.addEventListener('DOMContentLoaded', init);
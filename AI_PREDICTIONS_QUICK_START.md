# 🤖 AI Board Exam Predictions - Quick Start Guide

## Problem: Why do I need to train data or open APIs?

Your AI prediction system has **2 parts**:

1. **Database (MySQL)** ✅ Always running with Laragon
2. **Python AI APIs** ⚠️ Need to be started manually

### The Issue:
- Your **models are already trained** (saved in `models/` folders)
- But the **Python API servers need to be running** to serve predictions
- When the API isn't running, PHP pages can't get predictions

---

## ✅ SOLUTION: One-Click API Starter

### Method 1: Start ALL APIs at Once (RECOMMENDED)

**Double-click this file:**
```
START_ALL_PREDICTION_APIS.bat
```

This will open 5 terminal windows running:
- CAS API (Port 5003)
- CBAA API (Port 5002)
- CCJE API (Port 5001)
- CTE API (Port 5004)
- Engineering API (Port 5000)

**Leave these windows open** while using the prediction features.

---

### Method 2: Start Individual Department APIs

Navigate to the department folder and double-click `start_api.bat`:

**CAS:**
```
prediction_cas\start_api.bat
```

**CBAA:**
```
prediction_cbaa\start_api.bat
```

**CCJE:**
```
prediction_ccje\start_api.bat
```

**CTE:**
```
prediction_cte\start_api.bat
```

**Engineering:**
```
prediction\start_api.bat
```

---

## 🔄 Auto-Start Feature (NEW)

I've added auto-start functionality to CAS predictions:

**File updated:** `prediction_cas_anonymous.php`
- Now automatically checks if API is running
- Tries to start it if not running
- You should see predictions without manual API startup

**To extend this to other departments:**
1. Copy `check_and_start_cas_api.php` 
2. Create versions for CBAA, CCJE, CTE, Engineering
3. Include at top of their prediction pages

---

## 📊 How the System Works

### Without Training (Current State):
```
Database → (Models Already Trained) → Saved in models/*.pkl
                                           ↓
                            Just need API server running
                                           ↓
                            PHP calls API → Gets predictions
```

### When You See "No Predictions":
```
PHP Page → API Call → ❌ API not running → No predictions shown
```

### After Starting APIs:
```
PHP Page → API Call → ✅ API running → Models loaded → Predictions shown
```

---

## 🎯 Quick Testing

### Test if APIs are Running:

**CAS:**
```
http://localhost:5003/api/model/info
```

**CBAA:**
```
http://localhost:5002/api/model/info
```

**CCJE:**
```
http://localhost:5001/api/model/info
```

**CTE:**
```
http://localhost:5004/api/model/info
```

**Engineering:**
```
http://localhost:5000/api/model/info
```

If you see JSON data, the API is running! ✅

---

## ⚙️ Do You Need to Retrain?

**NO!** You only need to retrain if:
- ❌ You add new board exam data to database
- ❌ You want to improve prediction accuracy
- ❌ Model files are deleted/corrupted

**Current Status:**
✅ Models trained and saved
✅ Just need APIs running
✅ No training required

---

## 🚀 Startup Options

### Option A: Manual (Current Method)
1. Open Laragon
2. Double-click `START_ALL_PREDICTION_APIS.bat`
3. Open browser → Your dashboard
4. Predictions work!

### Option B: Add to Windows Startup (Auto-start on boot)
1. Press `Win + R`
2. Type: `shell:startup`
3. Create shortcut to `START_ALL_PREDICTION_APIS.bat`
4. APIs auto-start when Windows boots

### Option C: Keep APIs Running (Recommended)
1. Start APIs once
2. Minimize terminal windows
3. Keep them running all day
4. Close only when done

---

## 🛠️ Troubleshooting

### Problem: "Connection refused" or "No predictions"
**Solution:** Start the API using `START_ALL_PREDICTION_APIS.bat`

### Problem: "Port already in use"
**Solution:** API is already running or another program uses that port
- Check if terminal window is already open
- Restart the API

### Problem: "Python not found"
**Solution:** Install Python 3.8+
- Download from https://www.python.org/
- Make sure "Add to PATH" is checked during install

### Problem: "Module not found"
**Solution:** Install dependencies
```cmd
cd prediction_cas
pip install -r requirements.txt
```

---

## 📝 Summary

**Before:**
- ❌ Manual API startup
- ❌ Confusing "train data" requirement
- ❌ APIs stop when terminal closes

**After:**
- ✅ One-click startup for all APIs
- ✅ Models already trained (no retraining needed)
- ✅ Auto-start feature for CAS
- ✅ Clear instructions

**Remember:** 
- Models = Already trained ✅
- APIs = Need to be running ✅
- Just run `START_ALL_PREDICTION_APIS.bat` → Predictions work! 🎉

---

**Created:** December 8, 2025  
**Updated:** December 8, 2025  
**Status:** Ready to use

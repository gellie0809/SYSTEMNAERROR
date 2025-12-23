"""
CAS Prediction System - Quick Test Script

This script verifies that the CAS prediction system is properly set up.
"""

import os
import sys
import json

def check_file_exists(filepath, description):
    """Check if a file exists"""
    exists = os.path.exists(filepath)
    status = "✅" if exists else "❌"
    print(f"{status} {description}: {filepath}")
    return exists

def check_directory_exists(dirpath, description):
    """Check if a directory exists"""
    exists = os.path.exists(dirpath)
    status = "✅" if exists else "❌"
    print(f"{status} {description}: {dirpath}")
    return exists

def main():
    print("=" * 70)
    print("CAS PREDICTION SYSTEM - VERIFICATION TEST")
    print("=" * 70)
    print()
    
    all_passed = True
    
    # Check Python predictor file
    print("1. Checking CAS Predictor Module:")
    all_passed &= check_file_exists(
        "advanced_predictor_cas.py",
        "CAS Predictor Module"
    )
    print()
    
    # Check directory structure
    print("2. Checking Directory Structure:")
    all_passed &= check_directory_exists(
        "models/arts_and_sciences",
        "CAS Models Directory"
    )
    all_passed &= check_directory_exists(
        "output/arts_and_sciences",
        "CAS Output Directory"
    )
    all_passed &= check_directory_exists(
        "output/arts_and_sciences/graphs",
        "CAS Graphs Directory"
    )
    print()
    
    # Check if model is trained
    print("3. Checking Trained Models:")
    model_exists = check_file_exists(
        "models/arts_and_sciences/best_model.pkl",
        "CAS Trained Model"
    )
    metadata_exists = check_file_exists(
        "models/arts_and_sciences/model_metadata.json",
        "CAS Model Metadata"
    )
    
    if metadata_exists:
        with open("models/arts_and_sciences/model_metadata.json", 'r') as f:
            metadata = json.load(f)
            print(f"   ℹ️  Best Model: {metadata.get('best_model', 'N/A')}")
            print(f"   ℹ️  Training Records: {metadata.get('training_records', 'N/A')}")
            print(f"   ℹ️  Trained Date: {metadata.get('trained_date', 'N/A')}")
    print()
    
    # Check PHP file
    print("4. Checking Web Interface:")
    php_exists = os.path.exists("../prediction_cas.php")
    status = "✅" if php_exists else "❌"
    print(f"{status} CAS Prediction Page: ../prediction_cas.php")
    all_passed &= php_exists
    print()
    
    # Check API file
    print("5. Checking API Configuration:")
    api_exists = check_file_exists(
        "prediction_api.py",
        "Prediction API"
    )
    
    if api_exists:
        with open("prediction_api.py", 'r') as f:
            content = f.read()
            if "AdvancedBoardExamPredictorCAS" in content:
                print("   ✅ CAS Predictor imported in API")
            else:
                print("   ❌ CAS Predictor NOT imported in API")
                all_passed = False
            
            if "/api/cas/predict" in content:
                print("   ✅ CAS predict endpoint found")
            else:
                print("   ❌ CAS predict endpoint NOT found")
                all_passed = False
                
            if "/api/cas/train" in content:
                print("   ✅ CAS train endpoint found")
            else:
                print("   ❌ CAS train endpoint NOT found")
                all_passed = False
    print()
    
    # Summary
    print("=" * 70)
    if all_passed and model_exists:
        print("✅ ALL CHECKS PASSED - CAS Prediction System Ready!")
        print("\nNext Steps:")
        print("1. Make sure API server is running: start_api.bat")
        print("2. Login as cas_admin@lspu.edu.ph")
        print("3. Navigate to prediction_cas.php")
    elif all_passed and not model_exists:
        print("⚠️  SETUP INCOMPLETE - Models Need Training")
        print("\nNext Steps:")
        print("1. Run: train_cas.bat")
        print("2. Start API server: start_api.bat")
        print("3. Login as cas_admin@lspu.edu.ph")
        print("4. Navigate to prediction_cas.php")
    else:
        print("❌ SETUP INCOMPLETE - Some components missing")
        print("\nPlease check the missing files/directories above")
    print("=" * 70)

if __name__ == "__main__":
    main()

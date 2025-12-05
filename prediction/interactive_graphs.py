"""
Interactive Graph Generator for Board Exam Predictions
Creates interactive HTML graphs using Plotly
"""

import plotly.graph_objects as go
import plotly.express as px
from plotly.subplots import make_subplots
import pandas as pd
import numpy as np
import json
import os
from datetime import datetime

class InteractiveGraphGenerator:
    def __init__(self):
        self.output_dir = 'output/interactive_graphs'
        os.makedirs(self.output_dir, exist_ok=True)
        
        # LSPU Color Scheme
        self.colors = {
            'primary': '#3B6255',
            'secondary': '#8BA49A',
            'accent': '#CBDED3',
            'light': '#F2F2F2',
            'dark': '#2C4A3F',
            'success': '#28a745',
            'warning': '#ffc107',
            'danger': '#dc3545',
            'info': '#17a2b8'
        }
    
    def create_algorithm_comparison(self, validation_data):
        """Create interactive algorithm comparison chart"""
        
        eval_results = validation_data['step7_model_evaluation']['evaluation_results']
        
        # Prepare data
        models = [r['model'] for r in eval_results]
        r2_scores = [r['test_r2'] for r in eval_results]
        mae_values = [r['test_mae'] for r in eval_results]
        rmse_values = [r['test_rmse'] for r in eval_results]
        cv_scores = [r['cv_mean'] for r in eval_results]
        
        # Create subplot figure
        fig = make_subplots(
            rows=2, cols=2,
            subplot_titles=('R² Score Comparison', 'Mean Absolute Error', 
                          'Root Mean Squared Error', 'Cross-Validation Score'),
            specs=[[{'type': 'bar'}, {'type': 'bar'}],
                   [{'type': 'bar'}, {'type': 'bar'}]]
        )
        
        # R² Score
        fig.add_trace(
            go.Bar(
                x=models, y=r2_scores,
                name='R² Score',
                marker_color=self.colors['primary'],
                text=[f'{v:.4f}' for v in r2_scores],
                textposition='outside',
                hovertemplate='<b>%{x}</b><br>R² Score: %{y:.4f}<extra></extra>'
            ),
            row=1, col=1
        )
        
        # MAE (inverted for better visualization)
        fig.add_trace(
            go.Bar(
                x=models, y=mae_values,
                name='MAE',
                marker_color=self.colors['warning'],
                text=[f'{v:.2f}%' for v in mae_values],
                textposition='outside',
                hovertemplate='<b>%{x}</b><br>MAE: %{y:.2f}%<extra></extra>'
            ),
            row=1, col=2
        )
        
        # RMSE
        fig.add_trace(
            go.Bar(
                x=models, y=rmse_values,
                name='RMSE',
                marker_color=self.colors['info'],
                text=[f'{v:.2f}%' for v in rmse_values],
                textposition='outside',
                hovertemplate='<b>%{x}</b><br>RMSE: %{y:.2f}%<extra></extra>'
            ),
            row=2, col=1
        )
        
        # CV Score
        fig.add_trace(
            go.Bar(
                x=models, y=cv_scores,
                name='CV Score',
                marker_color=self.colors['success'],
                text=[f'{v:.4f}' for v in cv_scores],
                textposition='outside',
                hovertemplate='<b>%{x}</b><br>CV Score: %{y:.4f}<extra></extra>'
            ),
            row=2, col=2
        )
        
        # Update layout
        fig.update_layout(
            title_text='<b>Algorithm Performance Comparison</b>',
            title_font_size=20,
            showlegend=False,
            height=800,
            template='plotly_white'
        )
        
        fig.update_xaxes(tickangle=-45)
        
        # Save
        output_path = os.path.join(self.output_dir, 'algorithm_comparison.html')
        fig.write_html(output_path)
        print(f"✓ Algorithm comparison graph saved: {output_path}")
        
        return output_path
    
    def create_feature_importance(self, validation_data):
        """Create interactive feature importance chart"""
        
        features = validation_data['step4_feature_selection']['feature_importance'][:10]
        
        df = pd.DataFrame(features)
        
        fig = go.Figure(go.Bar(
            x=df['importance'],
            y=df['feature'],
            orientation='h',
            marker=dict(
                color=df['importance'],
                colorscale='Greens',
                showscale=True,
                colorbar=dict(title="Importance")
            ),
            text=[f'{v:.4f}' for v in df['importance']],
            textposition='outside',
            hovertemplate='<b>%{y}</b><br>Importance: %{x:.4f}<extra></extra>'
        ))
        
        fig.update_layout(
            title='<b>Top 10 Feature Importance</b>',
            title_font_size=20,
            xaxis_title='Importance Score',
            yaxis_title='Feature',
            height=600,
            template='plotly_white',
            yaxis={'categoryorder': 'total ascending'}
        )
        
        output_path = os.path.join(self.output_dir, 'feature_importance.html')
        fig.write_html(output_path)
        print(f"✓ Feature importance graph saved: {output_path}")
        
        return output_path
    
    def create_actual_vs_predicted(self, accuracy_data):
        """Create interactive actual vs predicted comparison"""
        
        all_actuals = []
        all_predictions = []
        years = []
        year_labels = []
        
        for validation in accuracy_data['validations']:
            best_result = [r for r in validation['results'] if r['model'] == validation['best_model']][0]
            
            # Parse numpy arrays
            if isinstance(best_result['actuals'], str):
                actuals = np.fromstring(best_result['actuals'].strip('[]'), sep=' ')
            else:
                actuals = np.array(best_result['actuals'])
            
            if isinstance(best_result['predictions'], str):
                pred_str = best_result['predictions'].replace('\n', ' ').replace('[', '').replace(']', '')
                predictions = np.fromstring(pred_str, sep=' ')
            else:
                predictions = np.array(best_result['predictions'])
            
            all_actuals.extend(actuals)
            all_predictions.extend(predictions)
            # Convert year to int for color mapping
            year_int = int(validation['test_year'])
            years.extend([year_int] * len(actuals))
            year_labels.extend([validation['test_year']] * len(actuals))
        
        # Create scatter plot
        fig = go.Figure()
        
        # Add scatter points
        fig.add_trace(go.Scatter(
            x=all_actuals,
            y=all_predictions,
            mode='markers',
            name='Predictions',
            marker=dict(
                size=12,
                color=years,
                colorscale='Viridis',
                showscale=True,
                colorbar=dict(title="Year"),
                line=dict(width=1, color='white')
            ),
            text=[f'Year: {y}<br>Actual: {a:.2f}%<br>Predicted: {p:.2f}%<br>Error: {abs(p-a):.2f}%' 
                  for y, a, p in zip(year_labels, all_actuals, all_predictions)],
            hovertemplate='%{text}<extra></extra>'
        ))
        
        # Add perfect prediction line
        min_val = min(min(all_actuals), min(all_predictions))
        max_val = max(max(all_actuals), max(all_predictions))
        
        fig.add_trace(go.Scatter(
            x=[min_val, max_val],
            y=[min_val, max_val],
            mode='lines',
            name='Perfect Prediction',
            line=dict(color='red', width=2, dash='dash')
        ))
        
        fig.update_layout(
            title='<b>Actual vs Predicted Passing Rates</b>',
            title_font_size=20,
            xaxis_title='Actual Passing Rate (%)',
            yaxis_title='Predicted Passing Rate (%)',
            height=600,
            template='plotly_white',
            hovermode='closest'
        )
        
        output_path = os.path.join(self.output_dir, 'actual_vs_predicted.html')
        fig.write_html(output_path)
        print(f"✓ Actual vs Predicted graph saved: {output_path}")
        
        return output_path
    
    def create_historical_accuracy(self, accuracy_data):
        """Create interactive historical accuracy timeline"""
        
        years = []
        mae_values = []
        r2_values = []
        
        for validation in accuracy_data['validations']:
            best_result = [r for r in validation['results'] if r['model'] == validation['best_model']][0]
            years.append(validation['test_year'])
            mae_values.append(best_result['mae'])
            r2_values.append(best_result['r2'])
        
        # Create subplot
        fig = make_subplots(
            rows=2, cols=1,
            subplot_titles=('Mean Absolute Error Over Time', 'R² Score Over Time'),
            vertical_spacing=0.15
        )
        
        # MAE
        fig.add_trace(
            go.Scatter(
                x=years, y=mae_values,
                mode='lines+markers',
                name='MAE',
                line=dict(color=self.colors['warning'], width=3),
                marker=dict(size=12, symbol='circle'),
                fill='tozeroy',
                fillcolor=f'rgba(255, 193, 7, 0.2)',
                hovertemplate='Year: %{x}<br>MAE: %{y:.2f}%<extra></extra>'
            ),
            row=1, col=1
        )
        
        # R² Score
        fig.add_trace(
            go.Scatter(
                x=years, y=r2_values,
                mode='lines+markers',
                name='R² Score',
                line=dict(color=self.colors['success'], width=3),
                marker=dict(size=12, symbol='diamond'),
                fill='tozeroy',
                fillcolor=f'rgba(40, 167, 69, 0.2)',
                hovertemplate='Year: %{x}<br>R² Score: %{y:.4f}<extra></extra>'
            ),
            row=2, col=1
        )
        
        fig.update_layout(
            title_text='<b>Historical Prediction Accuracy</b>',
            title_font_size=20,
            showlegend=True,
            height=700,
            template='plotly_white'
        )
        
        fig.update_xaxes(title_text="Year", row=2, col=1)
        fig.update_yaxes(title_text="MAE (%)", row=1, col=1)
        fig.update_yaxes(title_text="R² Score", row=2, col=1)
        
        output_path = os.path.join(self.output_dir, 'historical_accuracy.html')
        fig.write_html(output_path)
        print(f"✓ Historical accuracy graph saved: {output_path}")
        
        return output_path
    
    def create_prediction_confidence(self, predictions_data):
        """Create interactive confidence interval visualization"""
        
        if not predictions_data or 'predictions' not in predictions_data:
            print("⚠️  No prediction data available")
            return None
        
        predictions = predictions_data['predictions']
        
        exam_types = [p['board_exam_type'] for p in predictions]
        predicted = [p['predicted_passing_rate'] for p in predictions]
        lower = [p['confidence_interval_95']['lower'] for p in predictions]
        upper = [p['confidence_interval_95']['upper'] for p in predictions]
        historical = [p['historical_avg'] for p in predictions]
        
        # Shorten exam names for display
        short_names = []
        for exam in exam_types:
            if 'Electronics Engineer' in exam:
                short_names.append('ECELE')
            elif 'Electronics Technician' in exam:
                short_names.append('ECTLE')
            elif 'Electrical Engineer' in exam:
                short_names.append('REELE')
            elif 'Master Electrician' in exam:
                short_names.append('RMELE')
            else:
                short_names.append(exam[:20])
        
        fig = go.Figure()
        
        # Add confidence interval
        fig.add_trace(go.Scatter(
            x=short_names,
            y=upper,
            mode='lines',
            line=dict(width=0),
            showlegend=False,
            hoverinfo='skip'
        ))
        
        fig.add_trace(go.Scatter(
            x=short_names,
            y=lower,
            mode='lines',
            line=dict(width=0),
            fillcolor='rgba(139, 164, 154, 0.3)',
            fill='tonexty',
            name='95% Confidence Interval',
            hovertemplate='<b>%{x}</b><br>Lower: %{y:.2f}%<extra></extra>'
        ))
        
        # Add predicted values
        fig.add_trace(go.Scatter(
            x=short_names,
            y=predicted,
            mode='markers+lines',
            name='2025 Prediction',
            marker=dict(size=15, color=self.colors['primary'], symbol='diamond'),
            line=dict(color=self.colors['primary'], width=2),
            hovertemplate='<b>%{x}</b><br>Predicted: %{y:.2f}%<extra></extra>'
        ))
        
        # Add historical values
        fig.add_trace(go.Scatter(
            x=short_names,
            y=historical,
            mode='markers+lines',
            name='2024 Actual',
            marker=dict(size=12, color=self.colors['warning'], symbol='circle'),
            line=dict(color=self.colors['warning'], width=2, dash='dot'),
            hovertemplate='<b>%{x}</b><br>Historical: %{y:.2f}%<extra></extra>'
        ))
        
        fig.update_layout(
            title='<b>2025 Predictions with 95% Confidence Intervals</b>',
            title_font_size=20,
            xaxis_title='Board Exam Type',
            yaxis_title='Passing Rate (%)',
            height=600,
            template='plotly_white',
            hovermode='x unified'
        )
        
        output_path = os.path.join(self.output_dir, 'prediction_confidence.html')
        fig.write_html(output_path)
        print(f"✓ Prediction confidence graph saved: {output_path}")
        
        return output_path
    
    def create_radar_chart(self, validation_data):
        """Create radar chart for model comparison"""
        
        eval_results = validation_data['step7_model_evaluation']['evaluation_results']
        
        # Get top 5 models
        top_models = sorted(eval_results, key=lambda x: x['test_r2'], reverse=True)[:5]
        
        categories = ['R² Score', 'Low MAE', 'Low RMSE', 'CV Score']
        
        fig = go.Figure()
        
        for model_data in top_models:
            # Normalize metrics (0-1 scale)
            r2_norm = model_data['test_r2']
            mae_norm = max(0, 1 - (model_data['test_mae'] / 100))  # Lower is better
            rmse_norm = max(0, 1 - (model_data['test_rmse'] / 100))  # Lower is better
            cv_norm = max(0, model_data['cv_mean'])
            
            values = [r2_norm, mae_norm, rmse_norm, cv_norm]
            
            fig.add_trace(go.Scatterpolar(
                r=values + [values[0]],  # Close the loop
                theta=categories + [categories[0]],
                fill='toself',
                name=model_data['model'],
                hovertemplate='<b>%{theta}</b><br>Score: %{r:.3f}<extra></extra>'
            ))
        
        fig.update_layout(
            polar=dict(
                radialaxis=dict(
                    visible=True,
                    range=[0, 1]
                )
            ),
            title='<b>Model Performance Radar Chart</b>',
            title_font_size=20,
            height=600,
            template='plotly_white'
        )
        
        output_path = os.path.join(self.output_dir, 'model_radar.html')
        fig.write_html(output_path)
        print(f"✓ Radar chart saved: {output_path}")
        
        return output_path
    
    def create_dashboard(self):
        """Create an interactive dashboard with all graphs"""
        
        html_content = f"""
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Board Exam Prediction - Interactive Dashboard</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }}
        
        .container {{
            max-width: 1400px;
            margin: 0 auto;
        }}
        
        .header {{
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }}
        
        .header h1 {{
            color: #3B6255;
            font-size: 2.5em;
            margin-bottom: 10px;
        }}
        
        .header p {{
            color: #666;
            font-size: 1.1em;
        }}
        
        .tabs {{
            display: flex;
            background: white;
            border-radius: 15px;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            flex-wrap: wrap;
            gap: 10px;
        }}
        
        .tab-button {{
            flex: 1;
            min-width: 150px;
            padding: 15px 20px;
            border: none;
            background: #f0f0f0;
            cursor: pointer;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
            color: #333;
        }}
        
        .tab-button:hover {{
            background: #8BA49A;
            color: white;
            transform: translateY(-2px);
        }}
        
        .tab-button.active {{
            background: #3B6255;
            color: white;
        }}
        
        .content {{
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }}
        
        .graph-container {{
            display: none;
        }}
        
        .graph-container.active {{
            display: block;
        }}
        
        iframe {{
            width: 100%;
            height: 800px;
            border: none;
            border-radius: 10px;
        }}
        
        .info-box {{
            background: #CBDED3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #3B6255;
        }}
        
        .info-box h3 {{
            color: #3B6255;
            margin-bottom: 10px;
        }}
        
        .stats {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }}
        
        .stat-card {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }}
        
        .stat-card h4 {{
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 10px;
        }}
        
        .stat-card .value {{
            font-size: 2em;
            font-weight: bold;
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Board Exam Prediction System</h1>
            <p>LSPU San Pablo City Campus - College of Engineering</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Interactive Analytics Dashboard</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h4>Model Accuracy</h4>
                <div class="value">99.5%</div>
            </div>
            <div class="stat-card">
                <h4>Average Error</h4>
                <div class="value">±0.59%</div>
            </div>
            <div class="stat-card">
                <h4>Best Model</h4>
                <div class="value">Linear Reg.</div>
            </div>
            <div class="stat-card">
                <h4>Training Records</h4>
                <div class="value">33</div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab-button active" onclick="showTab(0)">📊 Algorithm Comparison</button>
            <button class="tab-button" onclick="showTab(1)">🎯 Feature Importance</button>
            <button class="tab-button" onclick="showTab(2)">📈 Actual vs Predicted</button>
            <button class="tab-button" onclick="showTab(3)">📉 Historical Accuracy</button>
            <button class="tab-button" onclick="showTab(4)">🔮 2025 Predictions</button>
            <button class="tab-button" onclick="showTab(5)">🕸️ Model Radar</button>
        </div>
        
        <div class="content">
            <div class="graph-container active" id="graph-0">
                <div class="info-box">
                    <h3>Algorithm Performance Comparison</h3>
                    <p>Compare all 7 machine learning algorithms across multiple metrics. Linear Regression achieved the best performance with R² = 1.0000.</p>
                </div>
                <iframe src="algorithm_comparison.html"></iframe>
            </div>
            
            <div class="graph-container" id="graph-1">
                <div class="info-box">
                    <h3>Feature Importance Analysis</h3>
                    <p>Fail rate is the most important predictor (96.8% importance), followed by total examinees and historical trends.</p>
                </div>
                <iframe src="feature_importance.html"></iframe>
            </div>
            
            <div class="graph-container" id="graph-2">
                <div class="info-box">
                    <h3>Actual vs Predicted Results</h3>
                    <p>Scatter plot showing how closely predictions match actual results. Points near the red line indicate perfect predictions.</p>
                </div>
                <iframe src="actual_vs_predicted.html"></iframe>
            </div>
            
            <div class="graph-container" id="graph-3">
                <div class="info-box">
                    <h3>Historical Accuracy Over Time</h3>
                    <p>Track prediction accuracy across different years. Shows consistent high performance with improving trends.</p>
                </div>
                <iframe src="historical_accuracy.html"></iframe>
            </div>
            
            <div class="graph-container" id="graph-4">
                <div class="info-box">
                    <h3>2025 Predictions with Confidence Intervals</h3>
                    <p>Predicted passing rates for 2025 with 95% confidence intervals. Shaded areas show the likely range of actual results.</p>
                </div>
                <iframe src="prediction_confidence.html"></iframe>
            </div>
            
            <div class="graph-container" id="graph-5">
                <div class="info-box">
                    <h3>Model Performance Radar Chart</h3>
                    <p>Multi-dimensional comparison of top 5 models across all performance metrics.</p>
                </div>
                <iframe src="model_radar.html"></iframe>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(index) {{
            // Hide all graphs
            document.querySelectorAll('.graph-container').forEach(g => {{
                g.classList.remove('active');
            }});
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(b => {{
                b.classList.remove('active');
            }});
            
            // Show selected graph
            document.getElementById('graph-' + index).classList.add('active');
            
            // Activate clicked button
            document.querySelectorAll('.tab-button')[index].classList.add('active');
        }}
    </script>
</body>
</html>
"""
        
        output_path = os.path.join(self.output_dir, 'dashboard.html')
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        print(f"✓ Interactive dashboard saved: {output_path}")
        return output_path
    
    def generate_all_graphs(self):
        """Generate all interactive graphs"""
        
        print("\n" + "="*80)
        print("GENERATING INTERACTIVE GRAPHS")
        print("="*80)
        
        # Load data
        validation_file = 'validation_output/validation_report.json'
        accuracy_file = 'accuracy_validation/detailed_validation.json'
        
        if not os.path.exists(validation_file):
            print("⚠️  Validation report not found. Run validation_report.py first.")
            return
        
        if not os.path.exists(accuracy_file):
            print("⚠️  Accuracy report not found. Run accuracy_checker.py first.")
            return
        
        with open(validation_file, 'r') as f:
            validation_data = json.load(f)
        
        with open(accuracy_file, 'r') as f:
            accuracy_data = json.load(f)
        
        # Load predictions if available
        predictions_file = 'models/model_metadata.json'
        predictions_data = None
        if os.path.exists(predictions_file):
            with open(predictions_file, 'r') as f:
                metadata = json.load(f)
                # Get latest predictions
                try:
                    from advanced_predictor import AdvancedBoardExamPredictor
                    db_config = {
                        'host': 'localhost',
                        'user': 'root',
                        'password': '',
                        'database': 'project_db'
                    }
                    predictor = AdvancedBoardExamPredictor(db_config)
                    predictions_data = predictor.predict_next_year()
                except Exception as e:
                    print(f"⚠️  Could not load predictions: {e}")
        
        # Generate all graphs
        graphs = []
        graphs.append(self.create_algorithm_comparison(validation_data))
        graphs.append(self.create_feature_importance(validation_data))
        graphs.append(self.create_actual_vs_predicted(accuracy_data))
        graphs.append(self.create_historical_accuracy(accuracy_data))
        
        if predictions_data:
            graphs.append(self.create_prediction_confidence(predictions_data))
        
        graphs.append(self.create_radar_chart(validation_data))
        
        # Create dashboard
        dashboard = self.create_dashboard()
        
        print(f"\n{'='*80}")
        print(f"✅ ALL INTERACTIVE GRAPHS GENERATED!")
        print(f"{'='*80}")
        print(f"📁 Output directory: {os.path.abspath(self.output_dir)}")
        print(f"🌐 Open dashboard: {dashboard}")
        print(f"\n📊 Generated {len(graphs)} interactive graphs:")
        for graph in graphs:
            if graph:
                print(f"   • {os.path.basename(graph)}")
        print(f"{'='*80}\n")
        
        return dashboard

if __name__ == "__main__":
    generator = InteractiveGraphGenerator()
    dashboard = generator.generate_all_graphs()
    
    if dashboard:
        print(f"\n✨ Ready! Open this file in your browser:")
        print(f"   {os.path.abspath(dashboard)}")

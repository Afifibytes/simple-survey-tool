# 🚀 Deployment & Testing Guide

This guide provides comprehensive instructions for testing, building, and deploying the Micro Survey Tool in production environments.

## 📋 Table of Contents

1. [Integration Testing](#integration-testing)
2. [End-to-End Testing](#end-to-end-testing)
3. [Docker Production Build](#docker-production-build)
4. [Cloud Deployment](#cloud-deployment)
5. [Environment Configuration](#environment-configuration)
6. [Production Checklist](#production-checklist)

---

## 🧪 Integration Testing

### Running Integration Tests

The application includes comprehensive integration tests that verify the interaction between controllers, models, and the database.

```bash
# Run all integration tests
docker-compose exec app php artisan test tests/Feature/

# Run specific integration test files
docker-compose exec app php artisan test tests/Feature/SurveyIntegrationTest.php
docker-compose exec app php artisan test tests/Feature/ResponseIntegrationTest.php

# Run tests with coverage (requires Xdebug)
docker-compose exec app php artisan test --coverage
```

### Integration Test Coverage

The integration tests cover:

✅ **Survey Management Flow**
- Complete survey creation with questions
- Survey updates and question modifications
- Survey deletion with cascade cleanup
- Survey statistics calculation

✅ **Response Submission Flow**
- Response creation with NPS and text data
- AI follow-up question generation
- Response validation and error handling
- Session-based response tracking

✅ **AI Integration Flow**
- Gemini API interaction and prompt engineering
- Caching behavior and performance optimization
- Error handling and graceful degradation
- Service timeout and failure scenarios

✅ **Database Interactions**
- Model relationships and constraints
- Data persistence and retrieval
- Transaction handling and rollbacks

### Test Database Configuration

Integration tests use a dedicated test database configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
```

---

## 🎭 End-to-End Testing

### Laravel Dusk Setup (Recommended)

For comprehensive browser testing, set up Laravel Dusk:

```bash
# Install Laravel Dusk
docker-compose exec app composer require --dev laravel/dusk

# Install Dusk
docker-compose exec app php artisan dusk:install

# Run Dusk tests
docker-compose exec app php artisan dusk
```

### Key E2E Test Scenarios

#### 1. Admin Interface Testing

**Survey Creation Flow:**
```php
// tests/Browser/AdminSurveyTest.php
public function test_admin_can_create_survey()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/admin/surveys/create')
                ->type('name', 'E2E Test Survey')
                ->type('description', 'Testing survey creation')
                ->type('questions[0][text]', 'How satisfied are you?')
                ->select('questions[0][type]', 'nps')
                ->type('questions[1][text]', 'Any feedback?')
                ->select('questions[1][type]', 'text')
                ->press('Create Survey')
                ->assertPathIs('/admin/surveys/*')
                ->assertSee('Survey created successfully');
    });
}
```

**Response Analytics Testing:**
```php
public function test_admin_can_view_responses()
{
    $survey = Survey::factory()->create();
    Response::factory()->count(5)->create(['survey_id' => $survey->id]);

    $this->browse(function (Browser $browser) use ($survey) {
        $browser->visit("/admin/surveys/{$survey->id}")
                ->assertSee('5 responses')
                ->click('@view-responses')
                ->assertSee('Response Data')
                ->assertSee('NPS Score')
                ->assertSee('Open Text');
    });
}
```

#### 2. Public Survey Testing

**Survey Response Flow:**
```php
// tests/Browser/PublicSurveyTest.php
public function test_user_can_complete_survey()
{
    $survey = Survey::factory()->create();
    
    $this->browse(function (Browser $browser) use ($survey) {
        $browser->visit("/survey/{$survey->id}")
                ->assertSee($survey->name)
                ->click('@nps-score-9')
                ->type('open_text', 'Great service, very satisfied!')
                ->press('Submit Response')
                ->waitFor('@ai-followup', 10)
                ->assertSee('One more question')
                ->type('@ai-answer', 'The customer support was excellent')
                ->press('Submit Final Answer')
                ->waitFor('@thank-you', 5)
                ->assertSee('Thank You!');
    });
}
```

**Mobile Responsiveness Testing:**
```php
public function test_survey_works_on_mobile()
{
    $survey = Survey::factory()->create();
    
    $this->browse(function (Browser $browser) use ($survey) {
        $browser->resize(375, 667) // iPhone dimensions
                ->visit("/survey/{$survey->id}")
                ->assertVisible('@nps-scale')
                ->click('@nps-score-8')
                ->assertSelected('nps_score', '8')
                ->type('open_text', 'Mobile test feedback')
                ->press('Submit Response')
                ->assertSee('Thank you');
    });
}
```

### Cypress Alternative Setup

For teams preferring Cypress, create `cypress.config.js`:

```javascript
const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8000',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    viewportWidth: 1280,
    viewportHeight: 720,
  },
})
```

**Key Cypress Test Scenarios:**
```javascript
// cypress/e2e/survey-flow.cy.js
describe('Survey Flow', () => {
  it('completes full survey with AI follow-up', () => {
    cy.visit('/survey/1')
    cy.get('[data-cy=nps-score-9]').click()
    cy.get('[data-cy=open-text]').type('Excellent service!')
    cy.get('[data-cy=submit-response]').click()
    cy.get('[data-cy=ai-followup]', { timeout: 10000 }).should('be.visible')
    cy.get('[data-cy=ai-answer]').type('The staff was very helpful')
    cy.get('[data-cy=submit-followup]').click()
    cy.get('[data-cy=thank-you]').should('contain', 'Thank You!')
  })
})
```

---

## 🐳 Docker Production Build

### Building Production Image

```bash
# Build production image
docker build --target production -t survey-tool:latest .

# Build with specific version tag
docker build --target production -t survey-tool:v1.0.0 .

# Build for multiple architectures
docker buildx build --platform linux/amd64,linux/arm64 \
  --target production -t survey-tool:latest .
```

### Production Docker Compose

```bash
# Copy production environment file
cp .env.production .env

# Update environment variables
nano .env

# Start production stack
docker-compose -f docker-compose.prod.yaml up -d

# Run database migrations
docker-compose -f docker-compose.prod.yaml exec app php artisan migrate --force

# Optimize for production
docker-compose -f docker-compose.prod.yaml exec app php artisan optimize
docker-compose -f docker-compose.prod.yaml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yaml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yaml exec app php artisan view:cache
```

### Production Health Checks

The production setup includes health checks for all services:

```bash
# Check service health
docker-compose -f docker-compose.prod.yaml ps

# View health check logs
docker-compose -f docker-compose.prod.yaml logs app
```

---

## ☁️ Cloud Deployment

### Render Deployment

1. **Create Render Account** and connect your GitHub repository

2. **Create Web Service:**
   ```yaml
   # render.yaml
   services:
     - type: web
       name: survey-tool
       env: docker
       dockerfilePath: ./Dockerfile
       dockerContext: .
       dockerCommand: supervisord
       envVars:
         - key: APP_ENV
           value: production
         - key: APP_DEBUG
           value: false
         - key: APP_KEY
           generateValue: true
         - key: GEMINI_API_KEY
           sync: false
   ```

3. **Create Database:**
   - Add PostgreSQL database service
   - Update `DB_CONNECTION=pgsql` in environment

4. **Create Redis:**
   - Add Redis service
   - Configure `REDIS_URL` environment variable

### Fly.io Deployment

1. **Install Fly CLI:**
   ```bash
   curl -L https://fly.io/install.sh | sh
   ```

2. **Initialize Fly App:**
   ```bash
   fly launch --dockerfile Dockerfile
   ```

3. **Configure fly.toml:**
   ```toml
   app = "survey-tool"
   primary_region = "dfw"

   [build]
     dockerfile = "Dockerfile"
     target = "production"

   [env]
     APP_ENV = "production"
     APP_DEBUG = "false"

   [[services]]
     http_checks = []
     internal_port = 80
     processes = ["app"]
     protocol = "tcp"
     script_checks = []

     [services.concurrency]
       hard_limit = 25
       soft_limit = 20
       type = "connections"

     [[services.ports]]
       force_https = true
       handlers = ["http"]
       port = 80

     [[services.ports]]
       handlers = ["tls", "http"]
       port = 443

     [[services.tcp_checks]]
       grace_period = "1s"
       interval = "15s"
       restart_limit = 0
       timeout = "2s"
   ```

4. **Deploy:**
   ```bash
   fly deploy
   fly secrets set GEMINI_API_KEY=your_key_here
   fly ssh console -C "php artisan migrate --force"
   ```

### Railway Deployment

1. **Connect Repository** to Railway dashboard

2. **Configure Environment Variables:**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `GEMINI_API_KEY=your_key`
   - Database and Redis URLs (auto-configured)

3. **Add Services:**
   - PostgreSQL database
   - Redis cache

4. **Deploy:**
   - Railway auto-deploys on git push
   - Run migrations via Railway CLI or dashboard

---

## 🔧 Environment Configuration

### Critical Production Settings

```bash
# Security
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:SECURE_32_CHARACTER_KEY

# Database (use strong passwords)
DB_PASSWORD=SECURE_DATABASE_PASSWORD
DB_ROOT_PASSWORD=SECURE_ROOT_PASSWORD

# Redis (enable password protection)
REDIS_PASSWORD=SECURE_REDIS_PASSWORD

# Sessions (enable secure cookies)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# AI Service
GEMINI_API_KEY=your_production_api_key
```

### Performance Optimization

```bash
# Run after deployment
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue processing (if using queues)
php artisan queue:work --daemon
```

---

## ✅ Production Checklist

### Pre-Deployment

- [ ] All tests passing (unit, integration, e2e)
- [ ] Environment variables configured
- [ ] Database credentials secured
- [ ] SSL certificates configured
- [ ] Domain DNS configured
- [ ] Backup strategy implemented

### Post-Deployment

- [ ] Database migrations run successfully
- [ ] Application health check passing
- [ ] AI service integration working
- [ ] Monitoring and logging configured
- [ ] Performance optimization applied
- [ ] Security headers configured

### Monitoring Setup

```bash
# Application logs
tail -f storage/logs/laravel.log

# Database performance
SHOW PROCESSLIST;
SHOW ENGINE INNODB STATUS;

# Redis monitoring
redis-cli INFO
redis-cli MONITOR
```

This comprehensive guide ensures reliable testing, building, and deployment of your micro-survey tool in production environments.

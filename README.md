# Micro Survey Tool

A comprehensive survey application built with Laravel, featuring AI-powered follow-up questions and a responsive user interface.

## 🚀 Features

### Core Functionality
- **Survey Management**: Create, edit, and manage surveys with NPS and open text questions
- **AI Follow-up Questions**: Automatically generate contextual follow-up questions using OpenAI
- **Response Collection**: Collect and analyze survey responses with detailed analytics
- **Admin Dashboard**: Comprehensive admin interface for survey management
- **Responsive Design**: Mobile-first design that works on all devices

### Technical Features
- **Laravel 12**: Modern PHP framework with robust architecture
- **Docker Support**: Complete containerized development and deployment
- **Real-time Validation**: Client-side form validation with immediate feedback
- **Progressive Enhancement**: Works without JavaScript, enhanced with it
- **Caching**: Redis-based caching for optimal performance
- **Database**: MySQL with proper indexing and relationships

## 🏗️ Architecture

### Backend Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AdminController.php      # Dashboard overview
│   │   ├── SurveyController.php     # Survey CRUD operations
│   │   └── ResponseController.php   # Response handling & AI integration
│   └── Requests/
│       ├── StoreSurveyRequest.php   # Survey validation
│       ├── UpdateSurveyRequest.php  # Survey update validation
│       └── StoreResponseRequest.php # Response validation
├── Models/
│   ├── Survey.php                   # Survey model with relationships
│   ├── Question.php                 # Question model
│   └── Response.php                 # Response model
└── Services/
    └── AIQuestionGeneratorService.php # Gemini integration
```

### Frontend Structure
```
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php           # Admin layout
│   │   └── public.blade.php        # Public survey layout
│   ├── admin/                      # Admin interface templates
│   ├── survey/                     # Public survey templates
│   └── components/                 # Reusable components
├── js/
│   ├── components/                 # JavaScript components
│   ├── pages/                      # Page-specific functionality
│   └── utils/                      # Utility functions
└── sass/                          # SCSS organization (optional)
```

## 🐳 Docker Setup

### Prerequisites
- Docker and Docker Compose installed
- OpenAI API key (optional, for AI features)

### Quick Start

1. **Clone and Setup**
   ```bash
   git clone <repository-url>
   cd DesignPeer
   ```

2. **Configure Environment**
   ```bash
   # The .env file is already configured for Docker
   # Update GEMINI_API_KEY if you want AI features
   ```

3. **Start Services**
   ```bash
   docker-compose up -d
   ```

4. **Initialize Database**
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed
   ```

5. **Access Application**
   - Admin Dashboard: http://localhost:8000
   - Sample Survey: http://localhost:8000/survey/1

### Docker Services
- **app**: Laravel application (PHP 8.2 + Nginx)
- **database**: MySQL 8.0 database
- **redis**: Redis for caching and sessions

## 📱 Usage

### Admin Interface
1. **Dashboard**: Overview of surveys and responses
2. **Survey Management**: Create, edit, and delete surveys
3. **Response Analytics**: View detailed response data and statistics

### Public Survey Flow
1. **Survey Display**: Clean, mobile-friendly survey interface
2. **Response Submission**: AJAX-based submission with validation
3. **AI Follow-up**: Contextual follow-up questions (if enabled)
4. **Completion**: Thank you message and response confirmation

## 🔧 Configuration

### Environment Variables
```env
# Database (Docker)
DB_CONNECTION=mysql
DB_HOST=database
DB_DATABASE=survey_tool
DB_USERNAME=survey_user
DB_PASSWORD=secret

# Redis (Docker)
REDIS_HOST=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# Gemini Integration
GEMINI_API_KEY=your_api_key_here
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_TIMEOUT=30
```

### AI Features
- Set `GEMINI_API_KEY` to enable AI follow-up questions
- AI questions are generated based on open text responses using Google's Gemini 2.0 Flash
- Graceful fallback when AI service is unavailable

## 🧪 Testing

### Run Tests
```bash
# Inside Docker container
docker-compose exec app php artisan test

# Or run specific test suites
docker-compose exec app php artisan test --testsuite=Feature
```

### Manual Testing
1. Create a survey via admin interface
2. Submit responses via public survey URL
3. View analytics in admin dashboard
4. Test AI follow-up generation (requires API key)

## 📊 Database Schema

### Tables
- **surveys**: Survey metadata and configuration
- **questions**: Survey questions with types (NPS, text, AI follow-up)
- **responses**: User responses with AI follow-up data
- **users**: User authentication (future use)
- **cache**: Redis cache storage
- **jobs**: Queue system for background tasks

### Key Relationships
- Survey → Questions (1:many)
- Survey → Responses (1:many)
- Responses include AI-generated follow-up questions and answers

## 🎨 Frontend Features

### Responsive Design
- Mobile-first approach with Tailwind CSS
- Touch-friendly NPS rating interface
- Optimized for all screen sizes

### JavaScript Functionality
- **Survey Form**: AJAX submission with real-time validation
- **Mobile Menu**: Responsive navigation
- **Form Validation**: Client-side validation with error display
- **Progress Tracking**: Visual progress indicators
- **Auto-save**: Automatic progress saving (localStorage)

### Accessibility
- ARIA labels and descriptions
- Keyboard navigation support
- Screen reader announcements
- High contrast design elements

## 🚀 Deployment

### Production Deployment
1. **Build Production Image**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

2. **Environment Setup**
   - Configure production database
   - Set secure APP_KEY
   - Configure Redis for production
   - Set GEMINI_API_KEY for AI features

3. **Optimization**
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

### Hosting Platforms
- **Docker-compatible**: Any platform supporting Docker containers
- **Cloud Providers**: AWS, Google Cloud, Azure
- **PaaS**: Heroku, DigitalOcean App Platform
- **VPS**: Any Linux server with Docker support

## 📈 Performance

### Optimization Features
- **Redis Caching**: Response caching for AI questions
- **Database Indexing**: Optimized queries with proper indexes
- **Asset Compilation**: Minified CSS and JavaScript
- **Lazy Loading**: Efficient resource loading
- **CDN Ready**: Static assets can be served via CDN

### Monitoring
- Application logs via Laravel logging
- Database query monitoring
- Redis performance metrics
- AI API usage tracking

## 🔒 Security

### Security Features
- **CSRF Protection**: All forms protected against CSRF attacks
- **Input Validation**: Comprehensive server-side validation
- **SQL Injection Prevention**: Eloquent ORM with parameter binding
- **XSS Protection**: Blade template escaping
- **Rate Limiting**: API rate limiting for AI services

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Submit pull request

### Code Standards
- PSR-12 PHP coding standards
- Laravel best practices
- Comprehensive test coverage
- Clear commit messages

## 📄 License

This project is open-source software licensed under the MIT license.

## 🆘 Support

### Common Issues
1. **Docker not starting**: Check Docker daemon is running
2. **Database connection**: Verify MySQL container is healthy
3. **AI features not working**: Check GEMINI_API_KEY configuration
4. **Assets not loading**: Run `npm run build` and check Vite config

### Getting Help
- Check Docker logs: `docker-compose logs app`
- Review Laravel logs: `docker-compose exec app tail -f storage/logs/laravel.log`
- Database issues: `docker-compose exec database mysql -u survey_user -p survey_tool`

---

**Built with ❤️ using Laravel, Docker, and modern web technologies.**

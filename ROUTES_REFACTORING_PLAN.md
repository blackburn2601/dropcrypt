# DropCrypt Routes Refactoring Plan
**Date:** November 25, 2025  
**Status:** Planning Phase - No Changes Yet

---

## 📊 **Current Route Analysis**

### **Current Routes Structure:**

```
UI Routes (HTML/Twig):
  GET/POST /message/                    → app_message_new
  GET      /message/{accessToken}       → app_message_show

API Routes (JSON):
  POST     /message/api/create          → app_message_api_create
  GET      /message/api/{accessToken}   → app_message_api_show
  DELETE   /message/api/{accessToken}   → app_message_api_delete
```

### **Issues with Current Structure:**

1. ❌ **Inconsistent Prefixes**
   - UI routes: `/message/...`
   - API routes: `/message/api/...`
   - Mixed pattern is confusing

2. ❌ **API Routes Under UI Prefix**
   - API routes are nested under `/message/api/`
   - Should be at root `/api/` level

3. ❌ **Unclear Route Names**
   - `app_message_new` - not clear if it's form or creation
   - `app_message_show` - could be confused with API endpoint

4. ❌ **Route Collisions**
   - Both UI and API use `/{accessToken}` pattern
   - Can cause routing conflicts

---

## 🎯 **Proposed New Route Structure**

### **Option A: Separate API and UI Completely (Recommended)**

```
UI Routes (HTML/Twig):
  GET/POST /drops/create                → app_drops_create_form
  GET      /drops/view/{accessToken}    → app_drops_view

API Routes (JSON):
  POST     /api/drops                   → api_drops_create
  GET      /api/drops/{accessToken}     → api_drops_show
  DELETE   /api/drops/{accessToken}     → api_drops_delete
```

**Reasoning:**
- ✅ "drops" is more aligned with "DropCrypt" branding
- ✅ Clear separation: `/drops/` vs `/api/drops/`
- ✅ RESTful API structure (`POST /api/drops`, not `/api/drops/create`)
- ✅ No route collisions
- ✅ Descriptive route names

---

### **Option B: Keep "message" but Improve Structure**

```
UI Routes (HTML/Twig):
  GET/POST /messages/create             → app_messages_create_form
  GET      /messages/view/{accessToken} → app_messages_view

API Routes (JSON):
  POST     /api/messages                → api_messages_create
  GET      /api/messages/{accessToken}  → api_messages_show
  DELETE   /api/messages/{accessToken}  → api_messages_delete
```

**Reasoning:**
- ✅ Plural "messages" is more RESTful
- ✅ Clear separation between UI and API
- ✅ Standard REST conventions
- ✅ Less disruptive (similar naming)

---

### **Option C: Pure RESTful (API-First)**

```
API Routes (JSON):
  POST     /api/v1/messages             → api_v1_messages_create
  GET      /api/v1/messages/{token}     → api_v1_messages_show
  DELETE   /api/v1/messages/{token}     → api_v1_messages_delete

UI Routes (if kept):
  GET/POST /create                      → app_create_form
  GET      /view/{token}                → app_view_message
```

**Reasoning:**
- ✅ API versioning included (`/api/v1/`)
- ✅ Future-proof for API changes
- ✅ Shorter UI routes
- ✅ Emphasizes API-first approach

---

## 📋 **Recommended Approach: Option A**

**Why Option A?**
1. ✅ Aligns with branding ("DropCrypt" → "drops")
2. ✅ Clean separation of concerns
3. ✅ RESTful API conventions
4. ✅ No collisions between UI and API routes
5. ✅ Easy to understand and maintain

---

## 🔧 **Implementation Plan for Option A**

### **Phase 1: Backend Routes (MessageController.php)**

#### **1.1 Update Controller Route Prefix**

**Current:**
```php
#[Route('/message')]
class MessageController extends AbstractController
```

**New:**
```php
#[Route('')]  // No prefix, routes defined individually
class MessageController extends AbstractController
```

---

#### **1.2 Update UI Routes**

**Current:**
```php
#[Route('/', name: 'app_message_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response

#[Route('/{accessToken}', name: 'app_message_show', methods: ['GET'])]
public function show(string $accessToken): Response
```

**New:**
```php
#[Route('/drops/create', name: 'app_drops_create_form', methods: ['GET', 'POST'])]
public function createForm(Request $request): Response

#[Route('/drops/view/{accessToken}', name: 'app_drops_view', methods: ['GET'])]
public function view(string $accessToken): Response
```

**Changes:**
- ✅ Method renamed: `new()` → `createForm()`
- ✅ Method renamed: `show()` → `view()`
- ✅ Route path: `/message/` → `/drops/create`
- ✅ Route path: `/message/{accessToken}` → `/drops/view/{accessToken}`
- ✅ Route names updated

---

#### **1.3 Update API Routes**

**Current:**
```php
#[Route('/api/create', name: 'app_message_api_create', methods: ['POST'])]
public function apiCreate(Request $request): JsonResponse

#[Route('/api/{accessToken}', name: 'app_message_api_show', methods: ['GET'])]
public function apiShow(string $accessToken, Request $request): JsonResponse

#[Route('/api/{accessToken}', name: 'app_message_api_delete', methods: ['DELETE'])]
public function apiDelete(string $accessToken, Request $request): JsonResponse
```

**New:**
```php
#[Route('/api/drops', name: 'api_drops_create', methods: ['POST'])]
public function apiCreate(Request $request): JsonResponse

#[Route('/api/drops/{accessToken}', name: 'api_drops_show', methods: ['GET'])]
public function apiShow(string $accessToken, Request $request): JsonResponse

#[Route('/api/drops/{accessToken}', name: 'api_drops_delete', methods: ['DELETE'])]
public function apiDelete(string $accessToken, Request $request): JsonResponse
```

**Changes:**
- ✅ RESTful path: `/message/api/create` → `/api/drops` (POST)
- ✅ Path: `/message/api/{accessToken}` → `/api/drops/{accessToken}`
- ✅ Route names follow API convention: `api_drops_*`

---

### **Phase 2: Template Updates**

#### **2.1 Template References to Update**

**Files to update:**
```
backend/templates/message/create.html.twig
backend/templates/message/show.html.twig
backend/templates/base.html.twig (if references message routes)
```

**Changes needed:**
```twig
{# OLD #}
{{ path('app_message_new') }}
{{ path('app_message_show', {'accessToken': token}) }}

{# NEW #}
{{ path('app_drops_create_form') }}
{{ path('app_drops_view', {'accessToken': token}) }}
```

#### **2.2 Redirect Updates**

**In MessageController.php:**
```php
// OLD
return $this->redirectToRoute('app_message_show', ['accessToken' => $message->getAccessToken()]);

// NEW
return $this->redirectToRoute('app_drops_view', ['accessToken' => $message->getAccessToken()]);
```

---

### **Phase 3: Frontend JavaScript (if exists)**

#### **3.1 Update API Endpoint Calls**

**Files to check:**
```
backend/public/js/encryption.js
frontend/src/* (if React frontend exists)
```

**Changes:**
```javascript
// OLD
fetch('/message/api/create', {
fetch('/message/api/' + accessToken, {

// NEW
fetch('/api/drops', {
fetch('/api/drops/' + accessToken, {
```

---

### **Phase 4: Nginx Configuration**

#### **4.1 Update nginx default.conf (if has specific rules)**

**Current:**
```nginx
location /api {
    root /var/www/public;
    try_files $uri /index.php$is_args$args;
}
```

**Should work as-is** (no changes needed)

The existing `/api` location block will handle all `/api/drops/*` routes automatically.

---

### **Phase 5: Documentation Updates**

#### **5.1 Files to Update**

```
✏️ README.md
✏️ INFRASTRUCTURE.md
✏️ PRODUCTION_DEPLOYMENT.md
✏️ Any API documentation
```

**Update all route examples:**
- `/message/` → `/drops/create`
- `/message/api/create` → `/api/drops`
- `/message/api/{token}` → `/api/drops/{token}`

---

### **Phase 6: Testing & Validation**

#### **6.1 Test Checklist**

**UI Routes:**
- [ ] `GET /drops/create` - Shows create form
- [ ] `POST /drops/create` - Creates message, redirects correctly
- [ ] `GET /drops/view/{token}` - Shows message

**API Routes:**
- [ ] `POST /api/drops` - Creates message via API
- [ ] `GET /api/drops/{token}?keyHash=xxx` - Retrieves message
- [ ] `DELETE /api/drops/{token}?keyHash=xxx` - Deletes message

**Route Debugging:**
```bash
# List all routes
docker compose exec php php bin/console debug:router

# Check specific route
docker compose exec php php bin/console debug:router api_drops_create
```

---

## 📊 **Impact Analysis**

### **Breaking Changes:**

1. **Frontend/Client Applications:**
   - ❌ All API endpoints change
   - ❌ Any hardcoded `/message/api/*` paths will break
   - ✅ Need to update all API calls

2. **Bookmarks/Shared Links:**
   - ❌ Old message links (`/message/{token}`) won't work
   - ⚠️ Consider adding redirects for backwards compatibility

3. **Third-party Integrations:**
   - ❌ Any external services calling the API will break
   - ✅ Need to notify integrators

### **Non-Breaking Changes:**

1. **Database:**
   - ✅ No database changes needed
   - ✅ Entity stays the same (`Message`)

2. **Business Logic:**
   - ✅ No changes to message creation/retrieval logic
   - ✅ Only route/URL changes

---

## 🔄 **Backwards Compatibility Strategy**

### **Option: Add Redirect Routes (Temporary)**

Create temporary redirect routes to support old URLs:

```php
// Temporary backwards compatibility (remove after X months)
#[Route('/message/', name: 'app_message_new_legacy', methods: ['GET', 'POST'])]
public function legacyNew(Request $request): Response
{
    return $this->redirectToRoute('app_drops_create_form', [], Response::HTTP_MOVED_PERMANENTLY);
}

#[Route('/message/{accessToken}', name: 'app_message_show_legacy', methods: ['GET'])]
public function legacyShow(string $accessToken): Response
{
    return $this->redirectToRoute('app_drops_view', ['accessToken' => $accessToken], Response::HTTP_MOVED_PERMANENTLY);
}

#[Route('/message/api/create', name: 'app_message_api_create_legacy', methods: ['POST'])]
public function legacyApiCreate(Request $request): JsonResponse
{
    // For API, return error with new URL in message
    return new JsonResponse([
        'error' => 'This endpoint has moved',
        'new_endpoint' => '/api/drops',
        'documentation' => 'https://yourdomain.com/api-docs'
    ], Response::HTTP_MOVED_PERMANENTLY);
}
```

**Benefits:**
- ✅ Old links still work (with redirect)
- ✅ Gives time to update integrations
- ✅ Can be removed after transition period

---

## 📝 **Implementation Checklist**

### **Pre-Implementation:**
- [ ] Choose route naming option (A, B, or C)
- [ ] Review impact on existing integrations
- [ ] Plan migration timeline
- [ ] Create backup/rollback plan

### **Implementation Steps:**
1. [ ] Update MessageController.php routes
2. [ ] Update method names in MessageController.php
3. [ ] Update template files (*.twig)
4. [ ] Update redirect calls
5. [ ] Update frontend JavaScript (if exists)
6. [ ] Add backwards compatibility routes (optional)
7. [ ] Update documentation files
8. [ ] Clear Symfony cache
9. [ ] Test all routes manually
10. [ ] Run automated tests (if exist)

### **Post-Implementation:**
- [ ] Update external API documentation
- [ ] Notify API consumers of changes
- [ ] Monitor logs for 404 errors
- [ ] Schedule removal of legacy routes

---

## ⏱️ **Estimated Time**

- **Planning:** 30 minutes ✅ (This document)
- **Backend changes:** 30 minutes
- **Template updates:** 15 minutes
- **Frontend updates:** 30 minutes (if React exists)
- **Documentation:** 20 minutes
- **Testing:** 30 minutes
- **Total:** ~2.5 hours

---

## 🎯 **Recommendation Summary**

**Recommended: Option A - "drops" naming**

```
UI:  /drops/create, /drops/view/{token}
API: /api/drops, /api/drops/{token}
```

**Reasons:**
1. ✅ Better branding alignment ("DropCrypt" → "drops")
2. ✅ Clearer separation (UI vs API)
3. ✅ RESTful conventions
4. ✅ Shorter, more memorable URLs
5. ✅ Future-proof structure

---

## 🚦 **Next Steps**

1. **Review this plan** and choose preferred option (A, B, or C)
2. **Approve the plan** or request modifications
3. **Implement changes** following the checklist
4. **Test thoroughly** to ensure nothing breaks
5. **Update documentation** with new routes

---

**Ready to proceed?** Let me know which option you prefer and I'll implement the changes! 🚀


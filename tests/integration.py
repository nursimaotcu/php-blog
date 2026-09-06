"""Run against a disposable database and a PHP server. Creates two test users."""
import http.cookiejar, urllib.request, urllib.parse, urllib.error, re, time, os
BASE=os.environ.get('BLOG_TEST_URL','http://127.0.0.1:8081')
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args): return None
class Client:
    def __init__(self):
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
    def request(self,path,data=None):
        body=urllib.parse.urlencode(data).encode() if data is not None else None
        try: r=self.opener.open(BASE+'/'+path,body,timeout=10)
        except urllib.error.HTTPError as e: r=e
        text=r.read().decode('utf-8')
        assert 'Warning:' not in text and 'Fatal error:' not in text, text[:300]
        return r.code,text,r.headers
    def token(self,path):
        status,text,_=self.request(path)
        assert status==200,(path,status,text[:200])
        return re.search(r'name="csrf" value="([a-f0-9]+)"',text)[1]
    def post(self,path,data,form=None):
        return self.request(path,{'csrf':self.token(form or path),**data})

a,b=Client(),Client();suffix=str(time.time_ns());pw='Demo-password-456'
assert a.request('create_post.php')[0]==302
assert a.request('register.php',{'username':'no-token'})[0]==403
for c,name in [(a,'a'),(b,'b')]:
    fields={'username':name+suffix,'email':name+suffix+'@example.test','password':pw}
    assert c.post('register.php',fields)[0]==302
    assert c.post('register.php',fields)[0]==409
    assert c.post('login.php',{'email':fields['email'],'password':'wrong-pass'})[0]==422
    assert c.post('login.php',{'email':fields['email'],'password':pw})[0]==302
print('PASS registration, duplicate email, login, invalid password and CSRF')
status,_,headers=a.post('create_post.php',{'title':'Demo <script>alert(1)</script>','content':'Test body','category_id':1,'tags':'php, test, php'})
assert status==302,(status,headers)
post_id=re.search(r'id=(\d+)',headers['Location'])[1]
detail='post_detail.php?id='+post_id
status,page,_=b.request(detail)
assert status==200 and '&lt;script&gt;' in page and '<script>alert(1)</script>' not in page
author_id=re.search(r'profile.php\?user_id=(\d+)',page)[1]
assert b.request('edit_post.php?id='+post_id)[0]==403
assert b.post('edit_post.php?id='+post_id,{'title':'bad','content':'bad'},form=detail)[0]==403
assert a.post('edit_post.php?id='+post_id,{'title':'Updated','content':'Updated body'})[0]==302
assert 'Updated body' in b.request(detail)[1]
assert a.request('delete_post.php?id='+post_id)[0]==405
assert b.post('add_comment.php',{'post_id':post_id,'comment_content':'Test comment'},form=detail)[0]==302
assert 'Test comment' in a.request(detail)[1]
assert b.post('like_post.php',{'id':post_id},form=detail)[0]==302
assert 'Beğenmekten Vazgeç' in b.request(detail)[1]
assert b.post('like_post.php',{'id':post_id},form=detail)[0]==302
assert 'Beğenmekten Vazgeç' not in b.request(detail)[1]
profile='profile.php?user_id='+author_id
assert b.post('follow_user.php',{'user_id':author_id},form=profile)[0]==302
assert 'Takipten Çık' in b.request(profile)[1]
assert b.post('follow_user.php',{'user_id':author_id},form=profile)[0]==302
assert 'Takipten Çık' not in b.request(profile)[1]
for path in ['index.php','index.php?category=1','users.php','top_users.php',profile]:
    assert a.request(path)[0]==200,path
assert b.post('delete_post.php',{'id':post_id},form=detail)[0]==302
assert 'Updated body' in a.request(detail)[1]
assert a.post('delete_post.php',{'id':post_id},form=detail)[0]==302
assert 'Gönderi bulunamadı' in a.request(detail)[1]
assert a.post('logout.php',{},form='index.php')[0]==302
assert a.request('create_post.php')[0]==302
print('PASS post create/edit/delete, ownership, GET rejection, escaping, comments, like/unlike, follow/unfollow, lists and logout')

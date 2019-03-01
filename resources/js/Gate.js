export default class Gate {
  constructor(user){
    this.user = user;
  }

  isAdmin(){
    return this.user.type === 'admin';
  }

  isAuthorOrUser(){
    if(this.user.type === 'author' || this.user.type === 'user'){
      return true;
    }

    return false;
  }

  isUser(){
    return this.user.type === 'user';
  }

  isAdminOrAuthor(){
    if(this.user.type === 'author' || this.user.type === 'admin'){
      return true;
    }

    return false;
  }
}
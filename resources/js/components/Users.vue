<template>
    <div class="container">
        <div class="row justify-content-center mt-5" v-if="$gate.isAdminOrAuthor()">
          <div class="col-md-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Users Table</h3>

                <div class="card-tools">
                    <button class="btn btn-success" @click="newModal"><i class="fas fa-user-plus"></i> Add New User</button>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <tbody>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Registered</th>
                        <th>Modify</th>
                    </tr>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.type | upText }}</td>
                        <td>{{ user.created_at | formatedDate }}</td>
                        <td>
                            <a href="#" @click="editModal(user)">
                                <i class="fa fa-edit blue"></i>
                            </a>
                            /
                            <a href="#" @click="deleteUser(user.id)">
                                <i class="fa fa-trash red"></i>
                            </a>
                        </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>

        <div class="row justify-content-center my-4" v-if="!$gate.isAdminOrAuthor()">
          <not-found></not-found>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="addNewUser" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 v-show="!editmode" class="modal-title" id="exampleModalLabel">Add New User</h5>
                    <h5 v-show="editmode" class="modal-title" id="exampleModalLabel">Update User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form @submit.prevent="editmode ? updateUser() : createUser()" @keydown="form.onKeydown($event)">
                    <div class="modal-body">
                        <div class="form-group">
                            <input v-model="form.name" type="text" name="name" placeholder="Name" class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                            <has-error :form="form" field="name"></has-error>
                        </div>

                        <div class="form-group">
                            <input v-model="form.email" type="email" name="email" placeholder="Email Address" class="form-control" :class="{ 'is-invalid': form.errors.has('email') }">
                            <has-error :form="form" field="email"></has-error>
                        </div>

                        <div class="form-group">
                            <textarea v-model="form.bio" name="bio" id="bio" placeholder="Short user's bio (optional)" class="form-control" :class="{ 'is-invalid': form.errors.has('bio') }"></textarea>
                            <has-error :form="form" field="bio"></has-error>
                        </div>
                        <div class="form-group">
                            <select name="type" v-model="form.type" id="type" class="form-control" :class="{ 'is-invalid': form.errors.has('type') }">
                                <option value="">Select User's Role</option>
                                <option value="admin">Admin</option>
                                <option value="user">Standard User</option>
                                <option value="author">Author</option>
                            </select>
                            <has-error :form="form" field="type"></has-error>
                        </div>

                        <div class="form-group">
                            <input v-model="form.password" placeholder="Password" type="password" name="password" class="form-control" :class="{ 'is-invalid': form.errors.has('password') }">
                            <has-error :form="form" field="password"></has-error>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button :disabled="form.busy" v-show="editmode" type="submit" class="btn btn-success">Update</button>
                        <button :disabled="form.busy" v-show="!editmode" type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
          return {
            editmode: true,
            users: {},
            form: new Form({
              id: '',
              name: '',
              email: '',
              password: '',
              type: '',
              bio: '',
              photo: ''
            })
          }
        },
        methods: {
          updateUser() {
            this.$Progress.start();
            // call update() in UserController
            this.form.put('api/user/' + this.form.id)
            .then(() => {
              // success
              $('#addNewUser').modal('hide');
              Swal.fire(
                'Updated!',
                'User has been updated.',
                'success'
                );
              Fire.$emit('Reload'); // reload page
              this.$Progress.finish();
            })
            .catch(() => {
              this.$Progress.fail();
            });
          },
          editModal(user) {
            this.editmode = true;
            $('#addNewUser').modal('show');
            this.form.fill(user);
          },
          newModal() {
            this.editmode = false;
            $('#addNewUser').modal('show');
          },
          deleteUser(id) {
              Swal.fire({
                  title: 'Are you sure?',
                  text: "You won't be able to revert this!",
                  type: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#d33',
                  confirmButtonText: 'Yes, delete it!'
                  }).then((result) => {
                      // send the request to server
                      if (result.value) {
                          this.form.delete('api/user/' + id)
                          .then(() => {                            
                              Swal.fire(
                              'Deleted!',
                              'User has been deleted.',
                              'success'
                              );
                              Fire.$emit('Reload');                           
                          })
                          .catch(() => {
                              Swal.fire("Failed!", "There was something wrong.", "warning");
                          });
                      }
                  });
          },
          createUser() {
              this.$Progress.start();
              // Submit the form via a POST request
              this.form.post('api/user')
                  .then(() => {
                      // Promise -> execute this only 
                      // in case everything is OK
                      
                      // fire custom event
                      Fire.$emit('Reload');
                      
                      $('#addNewUser').modal('hide');

                      Toast.fire({
                          type: 'success',
                          title: 'User created successfully'
                      });
                      this.$Progress.finish();   
                  })
                  .catch(() => {
                    this.$Progress.fail();
                  });                
          },
          loadUsers(){
            if(this.$gate.isAdminOrAuthor()){
              axios.get("api/user").then(({data}) => (this.users = data.data));
            }
          }
        },
        created(){
            this.loadUsers();
            // reload the table with new data
            Fire.$on('Reload', () => {
                this.loadUsers();
            });
        },
        mounted() {
            console.log('Users Component mounted.')
        }
    }
</script>
